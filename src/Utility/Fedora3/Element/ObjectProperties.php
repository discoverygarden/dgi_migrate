<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class ObjectProperties extends AbstractParser implements \ArrayAccess {
  const TAG = 'foxml:objectProperties';
  const MAP = [
    ObjectProperty::TAG => ObjectProperty::class,
  ];
  protected $properties = [];

  protected function pop() {
    $old = parent::pop();

    $this[$old->id()] = $old;

    return $old;
  }
  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'properties',
    ]);
  }

  public function offsetExists($offset) {
    return isset($this->properties[$offset]);
  }

  public function offsetGet($offset) {
    return $this->properties[$offset];
  }

  public function offsetSet($offset, $value) {
    if (!isset($this[$offset])) {
      $this->properties[$offset] = $value;
    }
    else {
      throw new Exception("Refusing to replace {$offset}.");
    }
  }

  public function offsetUnset($offset) {
    throw new Exception('Not implemented.');
  }
}
