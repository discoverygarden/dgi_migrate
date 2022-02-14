<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

use Drupal\dgi_migrate\Utility\Fedora3\Exceptions\LowLevelDereferenceFailedException;
use Drupal\dgi_migrate\Utility\Fedora3\Exceptions\MissingLowLevelStorageAdapterException;

abstract class AbstractLowLevelAdapterManager implements LowLevelAdapterInterface {

  protected $adapters = [];
  protected $sortedAdapters = NULL;
  protected $validAdapters = NULL;

  /**
   * Hacky "generics" approach.
   *
   * @param \Drupal\dgi_migrate\Utility\Fedora3\LowLevelAdapterInterface $adapter
   *   The adapter to test.
   *
   * @throws \InvalidArgumentException if the interface does not match.
   */
  protected function matchesInterface(LowLevelAdapterInterface $adapter) {
    // No-op,
  }

  /**
   * Service collector callback; add the adapter.
   *
   * @param \Drupal\dgi_migrate\Utility\Fedora3\LowLevelAdapterInterface $adapter
   *   The adapter to add.
   * @param int $priority
   *   The priority of the adapter.
   *
   * @return AbstractLowLevelAdapterManager
   *   The current object.
   */
  public function addAdapter(LowLevelAdapterInterface $adapter, $priority = 0) {
    $this->matchesInterface($adapter);
    $adapters[$priority][] = $adapter;

    return $this;
  }

  /**
   * Get a sorted array of the adapters.
   *
   * @return \Drupal\dgi_migrate\Utility\Fedora3\LowLevelAdapterInterface[]
   *   The sorted array of adapters.
   */
  protected function sortAdapters() {
    $sorted = [];

    krsort($this->adapters);
    foreach ($this->adapters as $adapters) {
      $sorted = array_merge($sorted, $adapters);
    }

    return $sorted;
  }

  /**
   * Gets the adapters, sorted and memoized.
   *
   * @return \Drupal\dgi_migrate\Utility\Fedora3\LowLevelAdapterInterface[]
   *   The array of adapters.
   */
  protected function sorted() {
    return $this->sortedAdapters ??= $this->sortAdapters();
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    if ($this->validAdapters === NULL) {
      $this->validAdapaters = array_filter($this->sorted(), function (LowLevelAdapterInterface $interface) {
        return $interface->valid();
      });
    }

    return !empty($this->validAdapters);
  }

  /**
   * {@inheritdoc}
   */
  public function dereference($id) {
    if (!$this->valid()) {
      throw new MissingLowLevelStorageAdapterException();
    }

    foreach ($this->validAdapters as $adapter) {
      try {
        return $adapter->dereference($id);
      }
      catch (LowLevelDereferenceFailedException $e) {
        // No-op, try the next?... really, don't often expect multiple to be
        // configured... anyway.
      }
    }

    throw new LowLevelDereferenceFailedException($id);
  }
}
