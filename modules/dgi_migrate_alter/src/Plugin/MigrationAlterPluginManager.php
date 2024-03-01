<?php

namespace Drupal\dgi_migrate_alter\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the migration alter plugin manager.
 */
class MigrationAlterPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new MigrationAlterManager object.
   *
   * @param string $type
   *   The type of the plugin: spreadsheet, foxml.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      "Plugin/dgi_migrate_alter/$type",
      $namespaces,
      $module_handler,
      'Drupal\dgi_migrate_alter\Plugin\MigrationAlterInterface',
      'Drupal\dgi_migrate_alter\Annotation\MigrationAlter'
    );
    $this->alterInfo('dgi_migrate_alter_' . $type. '_info');
    $this->setCacheBackend($cache_backend, 'dgi_migrate_alter' . $type . '_plugins');
  }

}
