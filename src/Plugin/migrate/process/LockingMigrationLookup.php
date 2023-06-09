<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Override upstream `migration_lookup` plugin, with some additional locking.
 */
class LockingMigrationLookup extends ProcessPluginBase implements MigrateProcessInterface, ContainerFactoryPluginInterface {

  const CONTROL_LOCK = 'dgi_migrate_locking_migration_lookup_lock';
  const DEBUG = FALSE;

  /**
   * The wrapped `migration_lookup` plugin to do the work proper.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected MigrateProcessInterface $parent;

  /**
   * Memoized array of migrations referenced.
   *
   * @var array
   */
  protected array $migrations;

  /**
   * Flag if we presently are in possession of the "control" lock.
   *
   * @var bool
   */
  protected bool $hasControl = FALSE;

  /**
   * Flag if we might be in possession of any "migration" locks.
   *
   * @var bool
   */
  protected bool $hasMigrationLocks = FALSE;

  /**
   * Memoized array mapping migration IDs to lock names.
   *
   * @var array
   */
  protected array $lockMap;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Toggle; do locking, or allow pass-through to wrapped plugin.
   *
   * @var bool
   */
  protected bool $doLocking;

  /**
   * The migration being executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected MigrationInterface $migration;

  /**
   * An array of SplFileObjects, to facilitate locking.
   *
   * @var SplFileObject[]
   */
  protected array $lockFiles = [];

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // We do not need to do the locking with `no_stub`, as we would not be
    // creating any entities, so there would be no potential for creating
    // duplicates.
    $this->doLocking = empty($this->configuration['no_stub']) &&
      (getenv('DGI_MIGRATE__DO_MIGRATION_LOOKUP_LOCKING') === 'TRUE');
  }

  /**
   * List the migrations referenced by the current plugin.
   *
   * @return array
   *   The migrations referenced by the current plugin.
   */
  protected function getMigrations() : array {
    if (!isset($this->migrations)) {
      $this->migrations = (array) $this->configuration['migration'];
      if (array_key_exists('source_ids', $this->configuration)) {
        $this->migrations = array_merge($this->migrations, array_keys($this->configuration['source_ids']));
      }
      if (array_key_exists('stub_id', $this->configuration)) {
        $this->migrations[] = $this->configuration['stub_id'];
      }
      $this->migrations = array_unique($this->migrations);
    }

    return $this->migrations;
  }

  /**
   * List migrations mapped to lock names.
   *
   * @return array
   *   An associative array mapping migration names to lock names.
   */
  protected function getLockMap() : array {
    if (!isset($this->lockMap)) {
      $this->lockMap = array_combine(
        $this->getMigrations(),
        array_map([$this, 'getLockName'], $this->getMigrations())
      );
    }
    if (!$this->lockMap) {
      throw new MigrateException('Failed to map migration IDs to lock names.');
    }

    return $this->lockMap;
  }

  /**
   * Helper; build out an applicable lock name for a given migration.
   *
   * @param string $migration_name
   *   The migration name/ID for which to generate a lock name.
   *
   * @return string
   *   The lock name to use for the given migration.
   */
  protected function getLockName(string $migration_name) : string {
    return "dgi_migrate_locking_migration_lookup__migration__$migration_name";
  }

  /**
   * Acquire all migration locks.
   *
   * @param float $timeout
   *   The time for which to acquire the locks.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function acquireMigrationLocks(float $timeout = 30.0) : void {
    try {
      if (count($this->getLockMap()) > 1) {
        // More than one, we need to acquire the "control" lock before
        // proceeding, to avoid potential deadlocks.
        if (!$this->getControlLock()) {
          throw new MigrateException('Failed to acquire control lock.');
        }
      }

      if (!$this->hasMigrationLocks) {
        $this->hasMigrationLocks = TRUE;
        foreach ($this->getLockMap() as $migration => $lock_name) {
          if (!$this->acquireLock($lock_name)) {
            throw new MigrateException("Failed to acquire lock for '$migration'.");
          }
        }
      }
    }
    finally {
      $this->releaseControlLock();
    }
  }

  /**
   * Release all migration locks.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function releaseMigrationLocks() : void {
    if ($this->hasMigrationLocks) {
      foreach ($this->getLockMap() as $lock_name) {
        $this->releaseLock($lock_name);
      }
      $this->hasMigrationLocks = FALSE;
    }
  }

  /**
   * Helper; acquire control lock.
   *
   * @return bool
   *   TRUE if we acquired it; otherwise, FALSE.
   */
  protected function getControlLock() : bool {
    if (!$this->hasControl) {
      $this->hasControl = $this->acquireLock(static::CONTROL_LOCK);
    }
    return $this->hasControl;
  }

  /**
   * Get an \SplFileObject instance to act as the lock.
   *
   * @param string $name
   *   The name of the lock to acquire. Should result in a file being created
   *   under the temporary:// scheme of the same name, against which `flock`
   *   commands will be issued.
   *
   * @return \SplFileObject
   *   The \SplFileObject instance against which to lock.
   */
  protected function getLockFile(string $name) : \SplFileObject {
    if (!isset($this->lockFiles[$name])) {
      $file_name = "temporary://{$name}";
      touch($file_name);
      $this->lockFiles[$name] = $file = new \SplFileObject($file_name, 'w');
      $file->fwrite("This is a temporary lock file. If there are no migrations running, it should be safe to delete.");
    }

    return $this->lockFiles[$name];
  }

  /**
   * Helper; acquire the lock.
   *
   * @param string $name
   *   The name of the lock to acquire.
   *
   * @return bool
   *   TRUE on success. Should not be able to return FALSE, as we perform this
   *   in a blocking manner.
   */
  protected function acquireLock(string $name) : bool {
    return $this->getLockFile($name)->flock(LOCK_EX);
  }

  /**
   * Helper; Release the given lock.
   *
   * @param string $name
   *   The name of the lock to release.
   *
   * @return bool
   *   TRUE on success. Should not be able to return FALSE, unless we maybe did
   *   not hold the lock?
   */
  protected function releaseLock(string $name) : bool {
    return $this->getLockFile($name)->flock(LOCK_UN);
  }

  /**
   * Helper; release control lock.
   */
  protected function releaseControlLock() {
    if ($this->hasControl) {
      $this->releaseLock(static::CONTROL_LOCK);
      $this->hasControl = FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $log = function ($message, $level = MigrationInterface::MESSAGE_INFORMATIONAL) use ($row, $destination_property) {
      if (isset($this->migration) && static::DEBUG) {
        $this->migration->getIdMap()->saveMessage($row->getSourceIdValues(), "$destination_property: $message", $level);
      }
    };

    if (!$this->doLocking) {
      $log("Bypassing locking.");
      return $this->parent->transform($value, $migrate_executable, $row, $destination_property);
    }

    try {
      // Acquire locks for all referenced migrations.
      $log('Locking migrations.');
      $this->acquireMigrationLocks();
      $log('Locked migrations, running parent.');

      // Perform the lookup as per the wrapped transform.
      $result = $this->parent->transform($value, $migrate_executable, $row, $destination_property);
      $log("Parent run, releasing migration locks.");

      // Optimistically drop migration locks.
      $this->releaseMigrationLocks();
      $log("Releasing migration locks, returning.");
      return $result;
    }
    finally {
      // Drop migration locks, if we still have them.
      $this->releaseMigrationLocks();
    }

  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $process_plugin_manager */
    $process_plugin_manager = $container->get('plugin.manager.migrate.process');
    $instance->parent = $process_plugin_manager->createInstance('dgi_migrate_original_migration_lookup', $configuration, $migration);
    $instance->database = $container->get('database');
    $instance->migration = $migration;

    return $instance;
  }

}
