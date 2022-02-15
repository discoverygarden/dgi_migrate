<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Accesses a property from an object.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.subindex"
 * )
 *
 * Accepts:
 * - One of:
 *   - "index": An index to attempt to access.
 *   - "index_from_destination": The name of a destination property to
 *     dereference, to attempt to use as the index to access.
 * - "skip_row_if_missing": Deprecated in favour of "missing_behavior". A flag
 *   to indicate if the row should be skipped when we find there's no value.
 * - "missing_behaviour": A string indicating what should happen when we fail
 *   to find the target value. One of:
 *   - "abort": Stop the migration, throwing an exception.
 *   - "skip_process": Stop processing the property.
 *   - "skip_row": Skip processing/saving the row.
 */
class Subindex extends ProcessPluginBase {

  use MissingBehaviorTrait {
    getDefaultMissingBehavior as getDefaultMissingBehaviorTrait;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->missingBehaviorInit();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultMissingBehavior() {
    return !empty($this->configuration['skip_row_if_missing']) ?
      'skip_row' :
      $this->getDefaultMissingBehaviorTrait();
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value) && !($value instanceof \ArrayAccess)) {
      throw new MigrateException('Input should be array-like.');
    }
    elseif (isset($this->configuration['index'])) {
      $index = $this->configuration['index'];
    }
    elseif (isset($this->configuration['index_from_destination'])) {
      $_index = $this->configuration['index_from_destination'];

      if (!$row->hasDestinationProperty($_index)) {
        throw new MigrateException("$_index not present in row.");
      }

      $index = $row->getDestinationProperty($_index);
    }

    if (!isset($value[$index])) {
      throw $this->getMissingException(strtr('Missing :index when processing :property; behavior is :behavior.', [
        ':index' => $index,
        ':property' => $destination_property,
        ':behavior' => $this->missingBehavior,
      ]));
    }

    return $value[$index];
  }

}
