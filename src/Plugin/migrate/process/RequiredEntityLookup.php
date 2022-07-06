<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;

/**
 * Stronger entity lookup.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.required_entity_lookup",
 *   handle_multiples = TRUE
 * )
 */
class RequiredEntityLookup extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $result = parent::transform($value, $migrate_executable, $row, $destination_property);

    if (!$result) {
      throw new MigrateSkipRowException(strtr('Failed to find lookup entity value ":value" for property ":destination_property".', [
        ':value' => var_export($value, TRUE),
        ':destination_property' => $destination_property,
      ]));
    }

    return $result;
  }

}
