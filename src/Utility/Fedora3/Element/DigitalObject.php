<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class DigitalObject extends AbstractParser implements \ArrayAccess {
  const TAG = 'foxml:digitalObject';
  const MAP = [
    ObjectProperties::TAG => ObjectProperties::class,
    Datastream::TAG => Datastream::class,
  ];

  protected $properties = NULL;
  protected $datastreams = [];

  protected function pop() {
    $old = parent::pop();

    if ($old instanceof ObjectProperties) {
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

  public function __isset($prop) {
    return isset($this->properties[$prop]) || parent::__isset($prop);
  }
  public function __get($prop) {
    return isset($this->properties[$prop]) ?
      $this->properties[$prop]->value() :
      parent::__get($prop);
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

  public function models() {
    $dom = new \DOMDocument();
    $dom->load($this['RELS-EXT']->getUri());
    $xpath = new \DOMXPath($dom);
    $ns = [
      'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
      'fre' => 'info:fedora/fedora-system:def/relations-external#',
      'fm' => 'info:fedora/fedora-system:def/model#',
    ];

    foreach ($ns as $prefix => $uri) {
      $xpath->registerNamespace($prefix, $uri);
    }

    $models = [];
    foreach ($xpath->query('/rdf:RDF/rdf:Description/fm:hasModel/@rdf:resource') as $node) {
      $models[] = $node->nodeValue;
    }

    return $models;
  }
}
