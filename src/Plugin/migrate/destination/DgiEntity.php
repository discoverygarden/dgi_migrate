<?php

namespace Drupal\dgi_migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateDestination(
 *   id = "dgi_node"
 * )
 */
class DgiEntity extends EntityContentBase {

  /** @var string $entityType */
  public static $entityType = 'node';

  /**
   * The migration ID.
   *
   * @var string
   */
  protected string $migrationId;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $instance = parent::create($container, $configuration, 'entity:' . static::$entityType, $plugin_definition, $migration);
    $instance->migrationId = $migration->id();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;
    $entity = $this->getEntity($row, $old_destination_id_values);

    if (!$entity) {
      throw new MigrateException('Unable to get entity');
    }

    assert($entity instanceof ContentEntityInterface);

    if ($this->isEntityValidationRequired($entity)) {
      $this->validateEntity($entity);
    }

    if (!$entity->isNew()) {
      $entity->setNewRevision();
      $entity->setRevisionCreationTime(time());

      $revision_message = $this->generateRevisionMessage($entity);
      $entity->setRevisionLogMessage($revision_message);
    }

    $ids = $this->save($entity, $old_destination_id_values);

    if ($this->isTranslationDestination()) {
      $ids[] = $entity->language()->getId();
    }

    return $ids;
  }

  /**
   * Generates a detailed revision message based on the entity and row data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param \Drupal\migrate\Row $row
   *   The row object containing migration data.
   *
   * @return string
   *   The generated revision message.
   */
  protected function generateRevisionMessage(ContentEntityInterface $entity) {
    $entity_id = $entity->id();

    return sprintf('Migration %s generated new revision for NID %d', $this->migrationId, $entity_id);
  }
}
