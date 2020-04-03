<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class Datastream extends AbstractParser implements \ArrayAccess {
  const TAG = 'foxml:datastream';
  const MAP = [
    DatastreamVersion::TAG => DatastreamVersion::class,
  ];
  protected $versions = [];

  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'versions',
    ]);
  }

  protected function pop() {
    $old = parent::pop();

    $this[$old->id()] = $old;

    return $old;
  }

  public function id() {
    return $this->ID;
  }

  public function latest() {
    return end($this->versions);
  }

  public function offsetExists($offset) {
    return isset($this->versions[$offset]);
  }

  public function offsetGet($offset) {
    return $this->versions[$offset];
  }

  public function offsetSet($offset, $value) {
    if (!isset($this[$offset])) {
      $this->versions[$offset] = $value;
    }
    else {
      throw new Exception("Refusing to replace {$offset}.");
    }
  }

  public function offsetUnset($offset) {
    throw new Exception('Not implemented.');
  }

  public function getUri() {
    return $this->latest()->content()->getUri();
  }
}
