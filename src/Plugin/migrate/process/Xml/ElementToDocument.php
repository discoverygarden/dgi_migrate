<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Creates a DOMDocument using a given DOMElement.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.xml.element_to_document"
 * )
 */
class ElementToDocument extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!($value instanceof \DOMElement)) {
      throw new MigrateException('Input should be a DOMElement.');
    }

    $doc = new \DOMDocument();
    $doc->appendChild($doc->importNode($value));

    return $doc;
  }

}
