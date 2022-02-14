<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

/**
 * Object low-level adapter service collector.
 */
class ObjectLowLevelAdapterManager extends AbstractLowLevelAdapterManager {

  /**
   * {@inheritdoc}
   */
  protected function matchesInterface(LowLevelAdapterInterface $adapter) {
    if (!($adapter instanceof ObjectLowLevelAdapterInterface)) {
      throw new \InvalidArgumentException('Adapter is not instance of ObjectLowLevelAdapterInterface.');
    }

    parent::matchesInterface($adapter);
  }

}
