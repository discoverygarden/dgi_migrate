<?php

namespace Drupal\dgi_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\dgi_migrate\MigrationIterator;

/**
 * @MigrateSource(
 *   id = "dgi_migrate.source.migration"
 * )
 */
class Migration extends SourcePluginBase implements ContainerFactoryPluginInterface {

  protected $targetMigration;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->targetMigration = $migration_plugin_manager->createInstance($this->configuration['migration']);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new MigrationIterator($this->targetMigration->getIdMap(), 'currentDestination');
  }

  public function getIds() {
    return $this->targetMigration->getDestinationPlugin()->getIds();
  }

  public function fields() {
    return $this->getIds();
  }

  public function __toString() {
    return strtr('target migration: @migration', [
      '@migration' => (string) $this->targetMigration,
    ]);
  }
}
