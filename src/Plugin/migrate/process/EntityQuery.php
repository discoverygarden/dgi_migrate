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
    $query = $this->storage->getQuery()
      ->accessCheck();

    foreach (($this->configuration['static_conditions'] ?? []) as $info) {
      [$field, $descriptor] = $info;
      $query->condition($field, $descriptor);
    }
    foreach (($this->configuration['conditions'] ?? []) as $info) {
      [$field, $descriptor] = $info;
      $query->condition($field, $row->get($descriptor));
    }
    foreach (($this->configuration['empty'] ?? []) as $field) {
      $query->notExists($field);
    }

    $results = $query->execute();

    return empty($results) ? NULL : $results;
  }

}
