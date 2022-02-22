<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\foxml\Plugin\migrate\process\Parse;
use Drupal\foxml\Utility\Fedora3\FoxmlParser;
use Drupal\foxml\Utility\Fedora3\Element\DigitalObject;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parse FOXML.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.parse_foxml"
 * )
 *
 * @deprecated with the pulling out of things to the "foxml" module.
 */
class ParseFoxml extends Parse implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FoxmlParser $parser) {
    @trigger_error('\Drupal\dgi_migrate\Plugin\migrate\process\ParseFoxml has been deprected; use \Drupal\foxml\Plugin\migrate\process\Parse instead.', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $parser);
  }

}
