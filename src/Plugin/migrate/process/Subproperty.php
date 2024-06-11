<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Accesses a property from an object.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.subproperty"
 * )
 */
class Subproperty extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_object($value)) {
      throw new MigrateException(strtr('The passed value when processing !property is not an object; got a !type', [
        '!property' => $destination_property,
        '!type' => gettype($value),
      ]));
    }
    elseif (isset($this->configuration['property'])) {
      $prop = $this->configuration['property'];
    }
    elseif (isset($this->configuration['property_from_destination'])) {
      $_prop = $this->configuration['property_from_destination'];

      if (!$row->hasDestinationProperty($_prop)) {
        throw new MigrateException("Property '$_prop' is not in the row.");
      }
      $prop = $row->getDestinationProperty($_prop);

    }

    if (!isset($value->{$prop})) {
      throw new MigrateException("Property '$prop' is not on object.");
    }

    return $value->{$prop};
  }

}
