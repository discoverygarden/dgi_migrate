<?php

namespace Drupal\dgi_migrate_alter\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for CSV Alteration plugins.
 */
interface MigrationAlterInterface extends PluginInspectionInterface {

  /**
   * Alters the migrations.
   *
   * @param array $migrations
   *   The migrations array.
   */
  public function alterMigrations(array &$migrations);

}
