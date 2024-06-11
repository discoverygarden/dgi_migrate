<?php

namespace Drupal\dgi_migrate_devel\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Devel debug message helper.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate_devel.ddm"
 * )
 */
class Ddm extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // XXX: Is entirely the objective of this class to use ddm() so... suppress
    // warnings against its use.
    // phpcs:ignore Drupal.Functions.DiscouragedFunctions.Discouraged
    \ddm($value, $destination_property);
    return $value;
  }

}
