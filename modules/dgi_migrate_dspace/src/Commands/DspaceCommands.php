<?php

namespace Drupal\dgi_migrate_dspace\Commands;

use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

class DspaceCommands extends DrushCommands {

  protected MigrationPluginManagerInterface $migrationPluginManager;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $nodeStorage;

  public function __construct(
    MigrationPluginManagerInterface $migration_plugin_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * List the things.
   *
   * @command dgi_migrate_dspace:list
   * @field-labels
   *   nid: Node ID
   *   handle: Handle URL
   *   url: URL
   * @default-fields handle,url
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The data.
   */
  public function doList() : RowsOfFields {
    // XXX: There doesn't appear to be an "OutputFormatter" that operates on
    // \Iterator or \Traversable instances, so... throw things into an array...
    return new RowsOfFields(iterator_to_array($this->generateList()));
  }

  protected function generateList() : \Traversable {
    $migration = $this->migrationPluginManager->createInstance('dspace_nodes');

    $id_map = $migration->getIdMap();

    foreach ($id_map as $row) {
      $id = $row['destid1'];
      if (!$id) {
        // No ID? Error'd row?
        continue;
      }
      $entity = $this->nodeStorage->load($id);
      $to_yield = [
        'nid' => $id,
        'handle' => $entity->get('field_handle')->getString(),
        'url' => $entity->toUrl('canonical', ['absolute' => TRUE])->toString(),
      ];
      yield $to_yield;
    }
  }

}
