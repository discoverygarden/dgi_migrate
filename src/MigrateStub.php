<?php

namespace Drupal\dgi_migrate;

use Drupal\dgi_migrate\EventSubscriber\StubMigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\MigrateStub as CoreMigrateStub;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Extend to set rollback_action on created stubs.
 */
class MigrateStub extends CoreMigrateStub {

  /**
   * Lazy-loaded event dispatcher service.
   *
   * @var \Psr\EventDispatcher\EventDispatcherInterface|null
   */
  protected ?EventDispatcherInterface $eventDispatcher = NULL;

  /**
   * Lazy-loaded migrate message instance.
   *
   * @var \Drupal\migrate\MigrateMessageInterface|null
   */
  protected ?MigrateMessageInterface $migrateMessage = NULL;

  /**
   * {@inheritdoc}
   *
   * Copypasta with one small variation.
   */
  protected function doCreateStub(MigrationInterface $migration, array $source_ids, array $default_values = []) {
    $destination = $migration->getDestinationPlugin(TRUE);
    $process = $migration->getProcess();
    $id_map = $migration->getIdMap();
    $migrate_executable = new MigrateExecutable($migration);
    $row = new Row($source_ids + $migration->getSourceConfiguration(), $migration->getSourcePlugin()->getIds(), TRUE);
    $migrate_executable->processRow($row, $process);
    foreach ($default_values as $key => $value) {
      $row->setDestinationProperty($key, $value);
    }
    $destination_ids = [];
    try {
      $this->getEventDispatcher()->dispatch(new MigratePreRowSaveEvent($migration, $this->getMigrateMessage(), $row), StubMigrateEvents::PRE_SAVE);
      $destination_ids = $destination->import($row);
      $this->getEventDispatcher()->dispatch(new MigratePostRowSaveEvent($migration, $this->getMigrateMessage(), $row, $destination_ids), StubMigrateEvents::POST_SAVE);
    }
    catch (\Exception $e) {
      $id_map->saveMessage($row->getSourceIdValues(), $e->getMessage());
    }

    if ($destination_ids) {
      // XXX: Divergence here, additionally passing the rollback action from the
      // destination.
      $id_map->saveIdMapping($row, $destination_ids, MigrateIdMapInterface::STATUS_NEEDS_UPDATE, $destination->rollbackAction());
      return $destination_ids;
    }
    return FALSE;
  }

  /**
   * Helper; get event dispatcher service.
   *
   * @return \Psr\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher service.
   */
  protected function getEventDispatcher() : EventDispatcherInterface {
    return $this->eventDispatcher ??= \Drupal::service('event_dispatcher');
  }

  /**
   * Helper; get migration message instance.
   *
   * @return \Drupal\migrate\MigrateMessageInterface
   *   A migrate message instance.
   */
  protected function getMigrateMessage() : MigrateMessageInterface {
    return $this->migrateMessage ??= new MigrateMessage();
  }

}
