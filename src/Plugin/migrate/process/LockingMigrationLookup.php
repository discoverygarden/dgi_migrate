<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dgi_migrate\Plugin\dgi_migrate\locker\LockerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Override upstream `migration_lookup` plugin, with some additional locking.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.locking_migration_lookup"
 * )
 *
 * Accepts all the same as the core "migration_lookup" plugin, in addition to:
 * - "no_lock": Flag to explicitly skip locking, which should only be used when
 *   it is known that there's a one-to-one mapping between each set of
 *   parameters and each resultant value.
 * - "lock_context_keys": A mapping of migrations IDs to arrays of maps,
 *   mapping:
 *     - "offset": An array of offsets indexing into the `$value` passed to the
 *       `::transform()` call, to allow the lock(s) acquired to be more
 *       specific.
 *     - "hash": An optional string representing a pattern. If provided every
 *       '#' found will be replaced with hexit resulting from hashing the value
 *       "offset".
 *  - "locker": Optional ID of locker plugin to use. Should not be necessary to
 *    provide, but just to be completionist.
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
   * The migration stub service.
   *
   * @var \Drupal\migrate\MigrateStubInterface
   */
  protected MigrateStubInterface $migrateStub;

  /**
   * The migration lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected MigrateLookupInterface $migrateLookup;

  /**
   * The value from which to build the lock context.
   *
   * @var array
   */
  protected array $lockContext;

  /**
   * Array of lock context.
   *
   * @var array|mixed
   */
  protected $lockContextKeys;

  /**
   * Locker plugin instance to use to manage locks.
   *
   * @var \Drupal\dgi_migrate\Plugin\dgi_migrate\locker\LockerInterface
   */
  protected LockerInterface $locker;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // We do not need to do the locking with `no_stub`, as we would not be
    // creating any entities, so there would be no potential for creating
    // duplicates.
    $this->doLocking = empty($this->configuration['no_stub']) &&
      (getenv('DGI_MIGRATE__DO_MIGRATION_LOOKUP_LOCKING') === 'TRUE') &&
      !($this->configuration['no_lock'] ?? FALSE);

    $this->lockContextKeys = $this->configuration['lock_context_keys'] ?? [];
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
   * @return \Traversable
   *   Generated mapping of migration names to lock names.
   */
  protected function getLockMap() : \Traversable {
    if (!isset($this->lockMap)) {
      $this->lockMap = array_combine(
        $this->getMigrations(),
        array_map([$this, 'getLockName'], $this->getMigrations())
      );
    }

    if (!$this->lockMap) {
      throw new MigrateException('Failed to map migration IDs to lock names.');
    }

    if ($this->lockContextKeys) {
      $apply_context_keys = function ($migration, $name) {
        $parts = ["{$name}-extra_context"];

        if (isset($this->lockContextKeys[$migration])) {
          foreach ($this->lockContextKeys[$migration] as $info) {
            $value = NestedArray::getValue($this->lockContext, $info['offset']);

            if (($prefix = ($info['hash'] ?? FALSE))) {
              $hash = md5($value, FALSE);
              $prefix_offset = 0;
              $hash_offset = 0;
              while (($prefix_offset = strpos($prefix, '#', $prefix_offset)) !== FALSE) {
                $prefix[$prefix_offset++] = $hash[$hash_offset++];
              }
              $parts[] = $prefix;
            }
            else {
              $parts[] = $value;
            }
          }
        }

        return implode('/', $parts);
      };
      foreach ($this->lockMap as $migration => $original_name) {
        yield $migration => $apply_context_keys($migration, $original_name);
      }
    }
    else {
      foreach ($this->lockMap as $migration => $name) {
        yield $migration => $name;
      }
    }
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
    return "dgi_migrate/locking_migration_lookup/migration/$migration_name";
  }

  /**
   * Acquire all migration locks.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function acquireMigrationLocks(int $mode = LOCK_EX, bool &$would_block = FALSE) : bool {
    try {
      $lock_map = iterator_to_array($this->getLockMap());
      if (count($lock_map) > 1) {
        // More than one, we need to acquire the "control" lock before
        // proceeding, to avoid potential deadlocks.
        if (!$this->getControlLock()) {
          throw new MigrateException('Failed to acquire control lock.');
        }
      }

      if (!$this->hasMigrationLocks) {
        // Don't have 'em yet; initial acquisition.
        $this->hasMigrationLocks = TRUE;
      }
      else {
        // Attempting to "promote" the locks, no need to set that we have 'em.
      }

      foreach ($lock_map as $lock_name) {
        if (!$this->acquireLock($lock_name, $mode, $would_block)) {
          return FALSE;
        }
      }

      return TRUE;
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
      $this->hasControl = $this->locker->acquireControl();
    }
    return $this->hasControl;
  }

  /**
   * Helper; acquire the lock.
   *
   * @param string $name
   *   The name of the lock to acquire.
   * @param int $mode
   *   The mode with which to acquire the lock.
   * @param bool $would_block
   *   A reference to a boolean, to be updated if called with LOCK_NB and the
   *   call _would_ have blocked.
   *
   * @return bool
   *   TRUE on success. Should not be able to return FALSE (except with LOCK_NB)
   *   as we perform this in a blocking manner.
   */
  protected function acquireLock(string $name, int $mode = LOCK_EX, bool &$would_block = FALSE) : bool {
    return $this->locker->acquireLock($name, $mode, $would_block);
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
    return $this->locker->releaseLock($name);
  }

  /**
   * Helper; release control lock.
   */
  protected function releaseControlLock() {
    if ($this->hasControl) {
      $this->locker->releaseControl();
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
      $this->setLockContext((array) $value);
      return $this->doTransform($value, $migrate_executable, $row, $destination_property);
    }
    finally {
      // Drop migration locks, if we still have them.
      $this->releaseMigrationLocks();
      $this->setLockContext(NULL);
    }

  }

  /**
   * Set values for more-specific locking.
   *
   * @param mixed $values
   *   The values to set; or: NULL to reset.
   */
  protected function setLockContext($values = []) : void {
    if ($values === NULL) {
      // Clear the context.
      unset($this->lockContext);
    }
    $values = (array) $values;
    $this->lockContext = $values;
  }

  /**
   * Locking transformation.
   *
   * Adapted from the core `migration_lookup`, with locks sprinkled in.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   * @throws \Drupal\migrate\MigrateException
   *
   * @see https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/modules/migrate/src/Plugin/migrate/process/MigrationLookup.php#L194-276
   */
  protected function doTransform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $context = [
      'self' => FALSE,
      'source_id_values' => [],
      'lookup_migration_ids' => (array) $this->configuration['migration'],
    ];
    $lookup_migration_ids =& $context['lookup_migration_ids'];

    try {
      // Acquire shared lock to do the lookup.
      $this->acquireMigrationLocks(LOCK_SH);
      $destination_ids = $this->doLookup($value, $migrate_executable, $row, $destination_property, $context);

      if (!$destination_ids && !empty($this->configuration['no_stub'])) {
        return NULL;
      }

      if (!$destination_ids && ($context['self'] || isset($this->configuration['stub_id']) || count($lookup_migration_ids) == 1)) {
        // Non-blockingly attempt to promote lock from shared to exclusive. Drop
        // shared lock and reacquire as exclusive if we would block, to avoid
        // potential deadlock.
        $would_block = FALSE;
        if (!$this->acquireMigrationLocks(LOCK_EX | LOCK_NB, $would_block) && $would_block) {
          $this->releaseMigrationLocks();
          $this->acquireMigrationLocks(LOCK_EX);

          // Attempt lookup again, as something might have populated it while we
          // were blocked attempting to acquire the exclusive lock.
          $destination_ids = $this->doLookup($value, $migrate_executable, $row, $destination_property, $context);
        }
        if (!$destination_ids) {
          $destination_ids = $this->doStub($context);
        }
      }

      if ($destination_ids) {
        if (count($destination_ids) == 1) {
          return reset($destination_ids);
        }
        else {
          return $destination_ids;
        }
      }
    }
    finally {
      $this->releaseMigrationLocks();
    }

  }

  /**
   * Perform the lookup proper.
   *
   * @return array|null
   *   The array of destination ID info.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\migrate\MigrateSkipProcessException
   *
   * @see https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/modules/migrate/src/Plugin/migrate/process/MigrationLookup.php#L194-229
   */
  protected function doLookup($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property, array &$context) : ?array {
    $source_id_values =& $context['source_id_values'];
    $lookup_migration_ids =& $context['lookup_migration_ids'];

    $destination_ids = NULL;
    foreach ($lookup_migration_ids as $lookup_migration_id) {
      $lookup_value = $value;
      if ($lookup_migration_id == $this->migration->id()) {
        $context['self'] = TRUE;
      }
      if (isset($this->configuration['source_ids'][$lookup_migration_id])) {
        $lookup_value = array_values($row->getMultiple($this->configuration['source_ids'][$lookup_migration_id]));
      }
      $lookup_value = (array) $lookup_value;
      $this->skipInvalid($lookup_value);
      $source_id_values[$lookup_migration_id] = $lookup_value;

      // Re-throw any PluginException as a MigrateException so the executable
      // can shut down the migration.
      try {
        $destination_id_array = $this->migrateLookup->lookup($lookup_migration_id, $lookup_value);
      }
      catch (PluginNotFoundException $e) {
        $destination_id_array = [];
      }
      catch (MigrateException $e) {
        throw $e;
      }
      catch (\Exception $e) {
        throw new MigrateException(sprintf('A %s was thrown while processing this migration lookup', gettype($e)), $e->getCode(), $e);
      }

      if ($destination_id_array) {
        $destination_ids = array_values(reset($destination_id_array));
        break;
      }
    }

    return $destination_ids;
  }

  /**
   * Perform stub creation.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   * @throws \Drupal\migrate\MigrateException
   *
   * @see https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/modules/migrate/src/Plugin/migrate/process/MigrationLookup.php#L236-267
   */
  protected function doStub(&$context) {
    $self =& $context['self'];
    $source_id_values =& $context['source_id_values'];
    $lookup_migration_ids =& $context['lookup_migration_ids'];

    // If the lookup didn't succeed, figure out which migration will do the
    // stubbing.
    if ($self) {
      $stub_migration = $this->migration->id();
    }
    elseif (isset($this->configuration['stub_id'])) {
      $stub_migration = $this->configuration['stub_id'];
    }
    else {
      $stub_migration = reset($lookup_migration_ids);
    }
    // Rethrow any exception as a MigrateException so the executable can shut
    // down the migration.
    try {
      return $this->migrateStub->createStub($stub_migration, $source_id_values[$stub_migration], [], FALSE);
    }
    catch (\LogicException | PluginNotFoundException $e) {
      // For BC reasons, we must allow attempting to stub:
      // - a derived migration; and,
      // - a non-existent migration.
    }
    catch (MigrateException | MigrateSkipProcessException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      throw new MigrateException(sprintf('%s was thrown while attempting to stub: %s', get_class($e), $e->getMessage()), $e->getCode(), $e);
    }

  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $process_plugin_manager */
    $process_plugin_manager = $container->get('plugin.manager.migrate.process');
    $instance->parent = $process_plugin_manager->createInstance('dgi_migrate_original_migration_lookup', $configuration, $migration);
    $instance->database = $container->get('database');
    $instance->migration = $migration;
    $instance->migrateStub = $container->get('migrate.stub');
    $instance->migrateLookup = $container->get('migrate.lookup');

    /** @var \Drupal\dgi_migrate\LockerPluginManagerInterface $locker_plugin_manager */
    $locker_plugin_manager = $container->get('plugin.manager.dgi_migrate.locker');
    $locker_plugin_id = match(TRUE) {
      isset($plugin_definition['locker']) => $plugin_definition['locker'],
      getenv('DGI_MIGRATE_DEFAULT_LOCKER') => getenv('DGI_MIGRATE_DEFAULT_LOCKER'),
      default => 'flock',
    };
    $instance->locker = $locker_plugin_manager->createInstance($locker_plugin_id);

    return $instance;
  }

  /**
   * Skips the migration process entirely if the value is invalid.
   *
   * Copypasta from upstream.
   *
   * @param array $value
   *   The incoming value to check.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   *
   * @see https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/modules/migrate/src/Plugin/migrate/process/MigrationLookup.php#L279-291
   */
  protected function skipInvalid(array $value) {
    if (!array_filter($value, [$this, 'isValid'])) {
      throw new MigrateSkipProcessException();
    }
  }

  /**
   * Determines if the value is valid for lookup.
   *
   * The only values considered invalid are: NULL, FALSE, [] and "".
   *
   * @param string $value
   *   The value to test.
   *
   * @return bool
   *   Return true if the value is valid.
   *
   * @see https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/modules/migrate/src/Plugin/migrate/process/MigrationLookup.php#L293-306
   */
  protected function isValid($value) {
    return !in_array($value, [NULL, FALSE, [], ""], TRUE);
  }

}
