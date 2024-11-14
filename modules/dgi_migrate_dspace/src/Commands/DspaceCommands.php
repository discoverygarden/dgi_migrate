<?php

namespace Drupal\dgi_migrate_dspace\Commands;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * DSpace migration commands.
 */
class DspaceCommands extends DrushCommands {

  /**
   * Migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected MigrationPluginManagerInterface $migrationPluginManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Node storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $nodeStorage;

  /**
   * Constructor.
   */
  public function __construct(
    MigrationPluginManagerInterface $migration_plugin_manager,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * List the things.
   *
   * @command dgi_migrate_dspace:list
   */
  public function doList() : void {
    // XXX: Drush's "OutputFormatter" do not appear to handle iterators, so
    // dealing with arbitrary numbers of things gets dicey... elected to
    // avoid using it here and just dump out CSV.
    // Something of a header...
    fputcsv(STDOUT, ['nid', 'handle', 'url']);
    foreach ($this->generateList() as $row) {
      // ... and then the rows...
      fputcsv(STDOUT, $row);
    }
  }

  /**
   * Generate the values to list.
   *
   * @return \Traversable
   *   A generator of arrays with the keys:
   *   - nid: Node IDs.
   *   - handle: Handle URLs.
   *   - url: Canonical URLs to the given node.
   */
  protected function generateList() : \Traversable {
    $migration = $this->migrationPluginManager->createInstance('dspace_nodes');

    $id_map = $migration->getIdMap();

    foreach ($id_map as $row) {
      $id = $row['destid1'];
      if (!$id) {
        // No ID? Error'd/ignored row?
        continue;
      }
      $entity = $this->nodeStorage->load($id);
      $url = $entity->toUrl('canonical')
        ->setAbsolute(TRUE)
        // XXX: Undocumented as of writing; however, has issue to fix.
        // @see https://www.drupal.org/project/drupal/issues/3132334
        ->setOption('path_processing', FALSE);
      $to_yield = [
        'nid' => $id,
        'handle' => $entity->get('field_handle')->getString(),
        'url' => $url->toString(),
      ];
      yield $to_yield;
    }
  }

}
