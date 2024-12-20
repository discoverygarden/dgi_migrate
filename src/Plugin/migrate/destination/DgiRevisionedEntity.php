<?php

namespace Drupal\dgi_migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom destination plugin for generating entity revisions.
 *
 * This class extends the EntityContentBase class to provide a custom
 * destination plugin for Drupal migrations. It allows for the creation of
 * revisions during the migration process, and maintains revision history.
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\EntityContentBase
 *
 * @MigrateDestination(
 *   id = "dgi_revisioned_entity"
 * )
 */
class DgiRevisionedEntity extends EntityContentBase {

  /**
   * The entity type.
   *
   * @var string
   */
  public string $entityType;

  /**
   * The migration ID.
   *
   * @var string
   */
  protected string $migrationId;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $entity_type = $configuration['entity_type'] ?? 'node';
    $instance = parent::create($container, $configuration, 'entity:' . $entity_type, $plugin_definition, $migration);
    $instance->entityType = $entity_type;
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

      $revision_message = $this->generateRevisionMessage();
      $entity->setRevisionLogMessage($revision_message);
    }

    $ids = $this->save($entity, $old_destination_id_values);

    if ($this->isTranslationDestination()) {
      $ids[] = $entity->language()->getId();
    }

    return $ids;
  }

  /**
   * Generates a detailed revision message.
   *
   * @return string
   *   The generated revision message.
   */
  protected function generateRevisionMessage() {
    return sprintf('New revision generated by migration `%s`.', $this->migrationId);
  }

}
