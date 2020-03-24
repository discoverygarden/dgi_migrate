<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class DigitalObject extends AbstractParser implements \ArrayAccess {
  const TAG = 'foxml:digitalObject';
  const MAP = [
    ObjectProperties::TAG => ObjectProperties::class,
    Datastream::TAG => Datastream::class,
  ];

  protected $properties = NULL
  protected $datastreams = [];

  protected function pop() {
    $old = parent::pop();

    if ($old instanceof ObjectPropertes) {
      if ($this->properties === NULL) {
        $this->properties = $old;
      }
      else {
        throw new Exception('Too many "objectProperties" elements.');
      }
    }
    elseif ($old instanceof Datastream) {
      $this[$old->id()] = $old;
    }

    return $old;
  }

  public function __get($prop) {
    return $this->properties[$prop];
  }

  public function datastreams() {
    return $this->datastreams;
  }

  public function offsetExists($offset) {
    return isset($this->datastreams[$offset]);
  }

  public function offsetGet($offset) {
    return $this->datastreams[$offset];
  }

  public function offsetSet($offset, $value) {
    if (!isset($this[$offset])) {
      $this->datastreams[$offset] = $value;
    }
    else {
      throw new Exception("Refusing to replace {$offset}.");
    }
  }

  public function offsetUnset($offset) {
    throw new Exception('Not implemented.');
  }
}
