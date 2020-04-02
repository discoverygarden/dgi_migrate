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
   */
  abstract protected function load($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property);

}
