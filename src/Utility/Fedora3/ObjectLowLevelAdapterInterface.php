<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

/**
 * Object low-level adapter interface.
 */
interface ObjectLowLevelAdapterInterface extends LowLevelAdapterInterface, \IteratorAggregate {

  /**
   * Get iterator for all object IDs.
   *
   * @return \Iterator|\Traversable
   *   The iterator.
   *
   * @throws \Drupal\dgi_migrate\Utility\Fedora3\NotImplementedException if the given low-level storage has not
   *   implemented the capability.
   */
  public function getIterator();

}
