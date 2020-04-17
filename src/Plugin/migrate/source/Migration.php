<?php

namespace Drupal\dgi_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\dgi_migrate\MigrationIterator;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * @MigrateSource(
 *   id = "dgi_migrate.source.migration"
 * )
 */
class Migration extends SourcePluginBase implements ContainerFactoryPluginInterface {

  protected $migrationPluginManager;
  protected $targetMigration;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->migrationPluginManager = $migration_plugin_manager;
    $this->targetMigration = $this->migrationPluginManager->createInstance($this->configuration['migration']);
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
    return (array) $this->targetMigration->getDestinationPlugin()->getIds();
  }

  public function fields() {
    return $this->getIds();
  }

  public function __toString() {
    return strtr('target migration: @migration', [
      '@migration' => $this->targetMigration->id(),
    ]);
  }

  public function __sleep() {
    $vars = parent::__sleep();

    $to_suppress = [
      // XXX: Avoid serializing some DB things that we don't need.
      'iterator',
      'targetMigration',
    ];
    foreach ($to_suppress as $value) {
      $key = array_search($value, $vars);
      if ($key !== FALSE) {
        unset($vars[$key]);
      }
    }

    return $vars;
  }

  public function __wakeup() {
    parent::__wakeup();

    $this->targetMigration = $this->migrationPluginManager->createInstance($this->configuration['migration']);
  }
}
