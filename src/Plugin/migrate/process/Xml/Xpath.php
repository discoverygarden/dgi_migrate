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
use DOMXPath;

/**
 * Instantiate XPath processor.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.xml.xpath"
 * )
 */
class Xpath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!($value instanceof DOMDocument)) {
      throw new MigrateException('Input should be a DOMDocument.');
    }

    $xpath = new DOMXPath($value);

    if (isset($this->configuration['namespaces'])) {
      foreach ($this->configuration['namespaces'] as $prefix => $uri) {
        $xpath->registerNamespace($prefix, $uri);
      }
    }

    return $xpath;
  }

}
