<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\dgi_migrate\Plugin\migrate\process\MissingBehaviorTrait;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Parses X(HT)ML into a DOMDocument instance.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.xml.dom"
 * )
 */
abstract class AbstractDom extends ProcessPluginBase {

  use MissingBehaviorTrait;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->missingBehaviorInit();
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateException(strtr('Input should be a string; got: !type', [
        '!type' => gettype($value),
      ]));
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
