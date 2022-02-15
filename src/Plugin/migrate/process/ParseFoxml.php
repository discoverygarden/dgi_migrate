<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Drupal\foxml\Utility\Fedora3\FoxmlParser;
use Drupal\foxml\Utility\Fedora3\Element\DigitalObject;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parse FOXML.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.parse_foxml"
 * )
 */
class ParseFoxml extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The parser to use.
   *
   * @var \Drupal\foxml\Utility\Fedora3\FoxmlParser
   */
  protected $parser;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FoxmlParser $parser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('foxml.parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value) || !file_exists($value)) {
      throw new MigrateException('The passed value is not a file path.');
    }
    $parsed = $this->parser->parse($value);
    assert($parsed instanceof DigitalObject, "Parser parsed under $destination_property");
    return $parsed;
  }

}
