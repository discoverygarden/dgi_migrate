<?php

namespace Drupal\dgi_migrate;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\dgi_migrate\Attribute\Locker;
use Drupal\dgi_migrate\Plugin\dgi_migrate\locker\LockerInterface;

/**
 * Locker plugin manager service.
 */
final class LockerPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface, LockerPluginManagerInterface {

  /**
   * Constructor.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/dgi_migrate/locker',
      $namespaces,
      $module_handler,
      LockerInterface::class,
      Locker::class,
    );

    $this->alterInfo('dgi_migrate__locker_info');
    $this->setCacheBackend($cacheBackend, 'dgi_migrate__locker_plugins');
  }

  /**
   * {@inheritDoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) : string {
    return 'flock';
  }

}
