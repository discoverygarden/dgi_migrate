<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Queries for entities.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.entity_query"
 * )
 */
class EntityQuery extends AbstractEntityAccessor {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $query = $this->storage->getQuery();

    foreach ($this->configuration['conditions'] as $info) {
      list($field, $descriptor) = $info;
      $query->condition($field, $row->get($descriptor));
    }

    $results = $query->execute();

    return empty($results) ? NULL : $results;
  }

}
