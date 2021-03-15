<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

/**
 * Element handler for foxml:datastream.
 */
class Datastream extends AbstractParser implements \ArrayAccess {
  const TAG = 'foxml:datastream';
  const MAP = [
    DatastreamVersion::TAG => DatastreamVersion::class,
  ];

  /**
   * The array of versions present for the given datastream.
   *
   * @var \Drupal\dgi_migrate\Utility\Fedora3\Element\DatastreamVersion[]
   */
  protected $versions = [];

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'versions',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function pop() {
    $old = parent::pop();

    $this[$old->id()] = $old;

    return $old;
  }

  /**
   * Accessor for the ID of this datastream.
   *
   * @return string
   *   The ID of this datastream.
   */
  public function id() {
    return $this->ID;
  }

  /**
   * Accessor for the latest datastream version represented.
   *
   * @return \Drupal\dgi_migrate\Utility\Fedora3\Element\DatastreamVersion
   *   The latest datastream version.
   */
  public function latest() {
    return end($this->versions);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return isset($this->versions[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return $this->versions[$offset];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if (!isset($this[$offset])) {
      $this->versions[$offset] = $value;
    }
    else {
      throw new Exception("Refusing to replace {$offset}.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    throw new Exception('Not implemented.');
  }

  /**
   * Helper; grab the URI for the latest datastream version.
   *
   * @return string
   *   The URI of the latest datastream version.
   */
  public function getUri() {
    return $this->latest()->content()->getUri();
  }

}
