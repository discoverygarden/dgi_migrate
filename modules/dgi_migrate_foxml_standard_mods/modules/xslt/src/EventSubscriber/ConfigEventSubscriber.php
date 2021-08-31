<?php

namespace Drupal\dgi_migrate_foxml_standard_mods_xslt\EventSubscriber;

use Drupal\dgi_migrate_foxml_standard_mods_xslt\Form\Settings;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Dump the cache when relevant.
 */
class ConfigEventSubscriber implements EventSubscriberInterface {

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructor.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'dumpCacheIfRelevant',
      ConfigEvents::DELETE => 'dumpCacheIfRelevant',
    ];
  }

  /**
   * Clear the cache when our config changes.
   */
  public function dumpCacheIfRelevant(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() === Settings::CONFIG) {
      if ($this->migrationPluginManager instanceof CachedDiscoveryInterface) {
        $this->migrationPluginManager->clearCachedDefinitions();
      }
    }
  }

}
