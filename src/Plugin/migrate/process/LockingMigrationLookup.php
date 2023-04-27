<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Connection;
use Drupal\Core\Lock\LockBackendInterface;
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
   * Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected LockBackendInterface $lock;

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
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->doLocking = getenv('DGI_MIGRATE__DO_MIGRATION_LOOKUP_LOCKING') === 'TRUE';
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
    // XXX: May have to get creative with hashing... thinking max lock name is
    // something like 255 chars?
    // XXX: 255 character limit may be a non-issue?: https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Lock%21DatabaseLockBackend.php/function/DatabaseLockBackend%3A%3AnormalizeName/10
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
      if (!$this->getControlLock()) {
        throw new MigrateException('Failed to acquire control lock.');
      }
      if (!$this->hasMigrationLocks) {
        $this->hasMigrationLocks = TRUE;
        foreach ($this->getLockMap() as $migration => $lock_name) {
          while (!$this->lock->acquire($lock_name, $timeout)) {
            while ($this->lock->wait($lock_name));
          }
          if (!$this->lock->acquire($lock_name, $timeout)) {
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
        $this->lock->release($lock_name);
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
    while (!($this->hasControl = $this->lock->acquire(static::CONTROL_LOCK, 600))) {
      while ($this->lock->wait(static::CONTROL_LOCK));
    }
    return $this->hasControl;
  }

  /**
   * Helper; release control lock.
   */
  protected function releaseControlLock() {
    if ($this->hasControl) {
      $this->lock->release(static::CONTROL_LOCK);
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

    $transaction = $this->database->startTransaction();
    try {
      // Acquire locks for all referenced migrations.
      $log('Locking migrations.');
      $this->acquireMigrationLocks();
      $log('Locked migrations, running parent.');

      // Perform the lookup as per the wrapped transform.
      $result = $this->parent->transform($value, $migrate_executable, $row, $destination_property);
      $log("Parent run, commit transaction and releasing migration locks.");
      unset($transaction);
      // Optimistically drop migration locks.
      $this->releaseMigrationLocks();
      $log("Releasing migration locks, returning.");
      return $result;
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      throw $e;
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
    $instance->lock = $container->get('lock');
    $instance->database = $container->get('database');
    $instance->migration = $migration;

    return $instance;
  }

}
