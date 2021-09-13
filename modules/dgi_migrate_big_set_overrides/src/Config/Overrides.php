<?php

namespace Drupal\dgi_migrate_big_set_overrides\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;

class Overrides implements ConfigFactoryOverrideInterface {

  const CONFIG = 'dgi_migrate_big_set_overrides.settings';

  protected $config;
  protected $overrides;

  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    $this->config = $config_factory->getEditable(static::CONFIG);
    $this->overrides = $this->config->getOriginal('overrides');
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    foreach ($this->getOverrides($names) as $info) {
      NestedArray::setValue($overrides, [$info['config'], ...$info['parents']], $info['value']);
    }

    return $overrides;
  }

  /**
   * Filter to the overrides relevant to the given names.
   *
   * Generates the sequence of overrides applicable.
   *
   * @param string[] $names
   *   An array of config names for which to get the override info.
   *
   */
  protected function getOverrides($names) {
    foreach ($this->overrides as $override) {
      if (in_array($override['config'], $names)) {
        yield $override;
      }
    }
  }

  /**
   * Helper; check if we have an override in the given config.
   *
   * @param string $name
   *   The name of a configuration to test.
   *
   * @return bool
   *   TRUE if we do; otherwise, FALSE if we do not.
   */
  protected function hasOverride($name) {
    foreach ($this->getOverrides([$name]) as $override) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'dgi_migrate_big_set_overrides';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $meta = new CacheableMetadata();

    if ($this->hasOverride($name)) {
      $meta->addCacheableDependency($this->config);
    }

    return $meta;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInteraface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
