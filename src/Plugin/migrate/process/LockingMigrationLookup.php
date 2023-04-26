<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

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
 *
 * Ideally, could avoid locking by default... require setting an environment
 * variables (and checking for it), to avoid locking in single-threaded
 * contexts?
 */
class LockingMigrationLookup extends ProcessPluginBase implements MigrateProcessInterface, ContainerFactoryPluginInterface {

  const CONTROL_LOCK = 'dgi_migrate_locking_migration_lookup_lock';

  protected MigrateProcessInterface $parent;
  protected LockBackendInterface $lock;
  protected array $migrations;
  protected bool $hasControl = FALSE;
  protected array $lockMap;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->migrations = (array) $configuration['migration'];
    if (array_key_exists('source_ids', $configuration)) {
      $this->migrations = array_merge($this->migrations, array_keys($configuration['source_ids']));
    }
    if (array_key_exists('stub_id', $configuration)) {
      $this->migrations[] = $configuration['stub_id'];
    }
    $this->migrations = array_unique($this->migrations);
  }

  protected function getMigrations() {
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

  protected function getLockMap() {
    if (!isset($this->lockMap)) {
      $this->lockMap = array_combine($this->getMigrations(), array_map([$this, 'getLockName'], $this->getMigrations()));
    }

    return $this->lockMap;
  }

  protected function getLockName(string $migration_name) {
    // XXX: May have to get creative with hashing... thinking max lock name is
    // something like 255 chars?
    return "dgi_migrate_locking_migration_lookup__migration_$migration_name";
  }

  protected function acquireMigrationLocks(float $timeout = 30.0) {
    foreach ($this->getLockMap() as $migration => $lock_name) {
      if (!$this->lock->acquire($lock_name, $timeout)) {
        throw new MigrateException("Failed to acquire lock for '$migration'.");
      }
    }
  }
  protected function releaseMigrationLocks() {
    foreach ($this->getLockMap() as $lock_name) {
      $this->lock->release($lock_name);
    }
  }

  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      // Acquire lock-control lock.
      while (!$this->lock->acquire(static::CONTROL_LOCK, 600)) {}
      $this->hasControl = TRUE;
      // Acquire locks for all referenced migrations.
      $this->acquireMigrationLocks();
      // Drop lock control lock.
      $this->lock->release(static::CONTROL_LOCK);
      $this->hasControl = FALSE;
      // Perform the lookup as per the wrapped transform.
      return $this->parent->transform($value, $migrate_executable, $row, $destination_property);
    }
    finally {
      if ($this->hasControl) {
        $this->lock->release(static::CONTROL_LOCK);
        $this->hasControl = FALSE;
      }

      // Drop migration locks.
      $this->releaseMigrationLocks();
    }

  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $process_plugin_manager */
    $process_plugin_manager = $container->get('plugin.manager.migrate.process');
    $instance->parent = $process_plugin_manager->createInstance('dgi_migrate_original_migration_lookup', $configuration, $migration);
    $instance->lock = $container->get('lock');

    return $instance;
  }

}
