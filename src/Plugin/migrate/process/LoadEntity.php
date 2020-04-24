<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\EntityFieldDefinitionTrait;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Parse FOXML.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.load_entity"
 * )
 */
class LoadEntity extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  use EntityFieldDefinitionTrait;

  /**
   * Storage for the given entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_type_id = static::getEntityTypeId($configuration['entity_type']);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage($entity_type_id)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return is_array($value) ?
      $this->storage->loadMultiple($value) :
      $this->storage->load($value);
  }

}
