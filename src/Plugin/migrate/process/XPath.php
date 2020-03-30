<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\dgi_migrate\Utility\Fedora3\FoxmlParser;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateException;

/**
 * Call an accessor from an object.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.xpath"
 * )
 */
class XPath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!($value instanceof DOMDocument)) {
      throw new MigrateException('Input should be a DOMDocument instance.');
    }
    $xpath = new DOMXPath($value);
    if (isset($this->configuration['ns_map'])) {
      foreach ($this->configuration['ns_map'] as $prefix => $uri) {
        $xpath->registerNamespace($prefix, $uri);
      }
    }

    return $xpath->query($this->configuration['xpath']);
  }

}
