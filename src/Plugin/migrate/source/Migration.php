<?php

namespace Drupal\dgi_migrate\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dgi_migrate\MigrationIterator;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Perform a migration based upon the results of another migration.
 *
 * @MigrateSource(
 *   id = "dgi_migrate.source.migration"
 * )
 */
class Migration extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration plugin manager, so we can load the target migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The target migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $targetMigration;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->migrationPluginManager = $migration_plugin_manager;
    $this->targetMigration = $this->migrationPluginManager->createInstance($this->configuration['migration']);
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return (array) $this->targetMigration->getDestinationPlugin()->getIds();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->getIds();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return strtr('target migration: @migration', [
      '@migration' => $this->targetMigration->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    parent::__wakeup();

    $this->targetMigration = $this->migrationPluginManager->createInstance($this->configuration['migration']);
  }

}
