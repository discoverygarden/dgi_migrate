<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

/**
 * Datastream low-level adapter service collector.
 */
class DatastreamLowLevelAdapterManager extends AbstractLowLevelAdapterManager {

  /**
   * {@inheritdoc}
   */
  protected function matchesInterface(LowLevelAdapterInterface $adapter) {
    if (!($adapter instanceof DatastreamLowLevelAdapterInterface)) {
      throw new \InvalidArgumentException('Adapter is not instance of DatastreamLowLevelAdapterInterface.');
    }

    parent::matchesInterface($adapter);
  }

}
