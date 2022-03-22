<?php

namespace Drupal\dgi_migrate;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateStub as CoreMigrateStub;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Extend to set rollback_action on created stubs.
 */
class MigrateStub extends CoreMigrateStub {

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
      $destination_ids = $destination->import($row);
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

}
