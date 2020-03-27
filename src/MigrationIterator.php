<?php

namespace Drupal\dgi_migrate;

use Drupal\migrate\Plugin\MigrateIdMapInterface;

class MigrationIterator extends \IteratorIterator {
  protected $iterator;
  protected $method;

  public function __construct(\Traversable $iterator, $method) {
    if (!($iterator instanceof MigrateIdMapInterface)) {
      throw new Exception('Invalid object passed.');
    }
    elseif (!in_array($method, ['currentSource', 'currentDestination'])) {
      throw new Exception('Unrecognized method.');
    }
    parent::__construct($iterator);
    $this->iterator = $iterator;
    $this->method = $method;
  }

  public function current() {
    return call_user_func([$this->iterator, $this->method]);
  }
}
