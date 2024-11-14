<?php

namespace Drupal\dgi_migrate_big_set_overrides\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Config overrider.
 */
class Overrides implements ConfigFactoryOverrideInterface {

  const CONFIG = 'dgi_migrate_big_set_overrides.settings';

  /**
   * This module's config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The array of overrides.
   *
   * @var array
   */
  protected $overrides;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
  ) {
    $this->config = $config_factory->getEditable(static::CONFIG);
    // XXX: Appears to be NULL on module installation; however, the requests
    // following should pick up the overrides.
    $this->overrides = $this->config->getOriginal('overrides') ?? [];
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
   */
  protected function getOverrides(array $names) {
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
    // XXX: No nice test for existence of things from a generator/iterator... so
    // let's try to iterate, but ignore the variable.
    // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
    foreach ($this->getOverrides([$name]) as $_) {
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
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
