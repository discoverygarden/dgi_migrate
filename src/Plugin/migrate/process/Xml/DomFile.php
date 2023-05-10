<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Parses X(HT)ML into a DOMDocument instance.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.xml.domfile"
 * )
 */
class DomFile extends AbstractDom {

  /**
   * {@inheritdoc}
   */
  protected function load($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $dom = new \DOMDocument();

    if (!$dom->load($value)) {
      throw $this->getMissingException('Failed to parse XML from file.');
    }

    return $dom;
  }

}
