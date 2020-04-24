<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Parses X(HT)ML into a DOMDocument instance.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.xml.dom"
 * )
 */
abstract class AbstractDom extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateException('Input should be a string.');
    }

    return $this->load($value, $migrate_executable, $row, $destination_property);
  }

  /**
   * Load up the value into a DOMDocument instance.
   *
   * @return \DOMDocument
   *   The loaded DOMDocument instance.
   */
  abstract protected function load($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property);

}
