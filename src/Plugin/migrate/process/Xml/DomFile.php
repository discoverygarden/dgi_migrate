<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

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
      throw new MigrateException('Failed to parse XML.');
    }

    return $dom;
  }

}
