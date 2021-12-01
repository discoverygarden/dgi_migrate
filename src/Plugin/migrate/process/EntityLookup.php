<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup as Upstream;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin looks for existing entities.
 *
 * The extension simply adds the flexibility of allowing node ID or title for
 * the member_of_existing_entity column.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi.entity_lookup",
 *   handle_multiples = TRUE
 * )
 */
class EntityLookup extends Upstream {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    // If the source data is an empty array, return the same.
    if (gettype($value) === 'array' && count($value) === 0) {
      return [];
    }

    // In case of subfields ('field_reference/target_id'), extract the field
    // name only.
    $parts = explode('/', $destinationProperty);
    $destinationProperty = reset($parts);
    $this->determineLookupProperties($destinationProperty);

    $this->destinationProperty = isset($this->configuration['destination_field']) ? $this->configuration['destination_field'] : NULL;

    // Assume a node id is being passed if it is numeric.
    $this->lookupValueKey = is_numeric($value) ? 'nid' : 'title';

    return $this->query($value);
  }

}
