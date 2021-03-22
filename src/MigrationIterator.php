<?php

namespace Drupal\dgi_migrate;

use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Facilitate iteration over a migration, for source or destination IDs.
 */
class MigrationIterator extends \IteratorIterator {

  /**
   * The migration ID map over which to iterate.
   *
   * @var \Drupal\migrate\Plugin\MigrateIdMapInterface
   */
  protected $iterator;

  /**
   * The method to execute on the iterator to return the desired IDs.
   *
   * @var string
   */
  protected $method;

  /**
   * Constructor.
   *
   * @param \Traversable $iterator
   *   The iterator to wrap. Must be a
   *   \Drupal\migrate\Plugin\MigrateIdMapInterface instance.
   * @param string $method
   *   A method on the iterator to invoke instead of the base "::current()", in
   *   order to pull either source or destination IDs. One of:
   *   - currentSource: To receive source IDs; or,
   *   - currentDestintation: To receive destination IDs.
   */
  public function __construct(\Traversable $iterator, $method) {
    if (!($iterator instanceof MigrateIdMapInterface)) {
      throw new \Exception('Invalid object passed.');
    }
    elseif (!in_array($method, ['currentSource', 'currentDestination'])) {
      throw new \Exception('Unrecognized method.');
    }
    parent::__construct($iterator);
    $this->iterator = $iterator;
    $this->method = $method;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return call_user_func([$this->iterator, $this->method]);
  }

}
