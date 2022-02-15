<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Unpack an array.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.unpack_array",
 *   handle_multiples = TRUE
 * )
 */
class UnpackArray extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('The input was not an array.');
    }
    return array_merge(...$value);
  }

}
