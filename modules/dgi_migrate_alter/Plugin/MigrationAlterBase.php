<?php

namespace Drupal\dgi_migrate_alter\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\dgi_migrate_alter\Exception\MigrationNotFoundException;

/**
 * Base class for CSV Alteration plugins.
 */
abstract class MigrationAlterBase extends PluginBase implements MigrationAlterInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\dgi_migrate_alter\Exception\MigrationNotFoundException
   */
  final public function alterMigrations(array &$migrations) {
    $migration_id = $this->getPluginDefinition()['migration_id'];
    if (isset($migrations[$migration_id])) {
      $this->alter($migrations[$migration_id]);
    }
    else {
      throw new MigrationNotFoundException("Migration $migration_id not found.");
    }
  }

  /**
   * Alters the specified migration array provided by alterMigrations.
   *
   * This method is intended to be overridden in child classes.
   * Each child class should implement its own logic.
   *
   * @param array &$migration
   *   The migration array to alter.
   */
  abstract protected function alter(array &$migration);

}
