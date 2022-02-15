<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\EntityFieldDefinitionTrait;
use Drupal\migrate\ProcessPluginBase;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract entity processing plugin base.
 */
abstract class AbstractEntityAccessor extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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

}
