<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lookup the first image media which is a member of the given collection.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.find_first_image_media"
 * )
 */
class FindFirstImageMedia extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Node storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Media storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->mediaStorage = $entity_type_manager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $config, $plugin_id, $plugin_def) {
    return new static(
      $config,
      $plugin_id,
      $plugin_def,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $this->search($value);
  }

  /**
   * Search recursively, depth-first to try to find an eligible image.
   *
   * @param string|int $value
   *   The node ID for which to obtain an image.
   * @param array $visited
   *   Used internally during traversal to avoid endless loops.
   *
   * @return string|int|null
   *   The media ID, or NULL if we didn't find anything.
   */
  protected function search($value, array &$visited = []) {
    if (!in_array($value, $visited)) {
      $visited[] = $value;
    }
    else {
      // We've already searched this one... abort!
      return;
    }

    // Find all the nodes that are a member of the current.
    $nodes = $this->nodeStorage->getQuery()
      ->accessCheck()
      ->condition('field_member_of', $value)
      ->execute();

    if (!$nodes) {
      // No children, nothing to find.
      return;
    }

    // Find all the image media belonging to the nodes that are members of the
    // current.
    // XXX: "Representative images" are passed through the image styles, so we
    // should be fine ignoring the MIME-types here.
    $media = $this->mediaStorage->getQuery()
      ->accessCheck()
      ->condition('bundle', 'image')
      ->condition('field_media_of', $nodes, 'IN')
      ->sort('field_media_use.entity:taxonomy_term.field_external_uri', 'ASC')
      ->range(0, 1)
      ->execute();

    if ($media) {
      // Return the media if we found it.
      return reset($media);
    }

    // Otherwise, recurse over ALL the children.
    foreach ($nodes as $candidate) {
      $result = $this->search($candidate, $visited);
      if ($result) {
        return $result;
      }
    }
  }

}
