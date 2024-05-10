<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\StaticMap as Upstream;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Perform a mapping.
 *
 * The upstream implementation does not allow to map from values containing
 * periods... so to be able to map from URL-like URIs containing periods, let's
 * roll something that can.
 *
 * The biggest difference is: For our `map` parameter, we accept an array of
 * two-tuples representing the keys and values, instead of an associative array
 * directly.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.static_map"
 * )
 */
class StaticMap extends Upstream {

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // Remap our array of two-tuples onto the map structure.
    if (!($configuration['original_map_structure'] ?? FALSE)) {
      $configuration['map'] = array_column($configuration['map'], 1, 0);
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      return parent::transform($value, $migrate_executable, $row, $destination_property);
    }
    catch (MigrateSkipRowException $e) {
      if ($this->configuration['skip_process_instead_of_row'] ?? FALSE) {
        $message = strtr('Could not map ":value" when processing :property; aborting processing.', [
          ':value' => $value,
          ':property' => $destination_property,
        ]);
        $migrate_executable->saveMessage($message, MigrationInterface::MESSAGE_WARNING);
        throw new MigrateSkipProcessException($message, 0, $e);
      }
      else {
        throw $e;
      }
    }
  }

}
