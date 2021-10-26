<?php

namespace Drupal\dgi_migrate_devel\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Devel debug message helper.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate_devel.ddm"
 * )
 *
 */
class Ddm extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    \ddm($value, $destination_property);
    return $value;
  }

}
