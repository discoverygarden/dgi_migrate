<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\dgi_migrate\Utility\Fedora3\FoxmlParser;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use DOMDocument;

/**
 * Parses X(HT)ML into a DOMDocument instance.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.xml.domstring"
 * )
 */
class DomString extends AbstractDom {

  /**
   * Load up the value into a DOMDocument instance.
   *
   * @return \DOMDocument
   */
  protected function load($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $dom = new DOMDocument();

    if (!$dom->loadXML($value)) {
      throw new MigrateException('Failed to parse XML.');
    }

    return $dom;
  }

}
