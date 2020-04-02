<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\StaticMap as Upstream;

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
   * 
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // Remap our array of two-tuples onto the map structure.
    $configuration['map'] = array_column($configuration['map'], 1, 0);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}