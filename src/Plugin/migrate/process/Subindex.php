<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\dgi_migrate\Utility\Fedora3\FoxmlParser;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Accesses a property from an object.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.subindex"
 * )
 */
class Subindex extends ProcessPluginBase {

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
      if (empty($this->configuration['skip_row_if_missing'])) {
        throw new MigrateException('Index not present, extraction failed.');
      }
      else {
        throw new MigrateSkipRowException('Index not present, skipping row.');
      }
    }

    return $value[$index];
  }

}
