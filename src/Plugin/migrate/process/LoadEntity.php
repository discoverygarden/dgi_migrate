<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Loads an entity.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.load_entity"
 * )
 */
class LoadEntity extends AbstractEntityAccessor {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return is_array($value) ?
      $this->storage->loadMultiple($value) :
      $this->storage->load($value);
  }

}
