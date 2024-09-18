<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Ensure files to be directly referenced as not writable.
 *
 * More specifically, where source files are referenced by file entities, as
 * rolling back a migration will attempt to delete the files of those entities
 * that are rolled back. Rolling back a migration only to have all the files go
 * AWOL would be quite bad.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.non_writable",
 * )
 */
class NonWritable extends ProcessPluginBase {

  use EnsureNonWritableTrait;

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return static::ensureNonWritable($value);
  }

}
