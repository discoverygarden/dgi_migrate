<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

/**
 * Element handler for foxml:digitalObject.
 */
class DigitalObject extends AbstractParser implements \ArrayAccess {
  const TAG = 'foxml:digitalObject';
  const MAP = [
    ObjectProperties::TAG => ObjectProperties::class,
    Datastream::TAG => Datastream::class,
  ];

  /**
   * Object properties instance.
   *
   * @var \Drupal\dgi_migrate\Utility\Fedora3\Element\ObjectProperties
   */
  protected $properties = NULL;

  /**
   * Associative array mapping datastream IDs to the instances representing.
   *
   * @var \Drupal\dgi_migrate\Utility\Fedora3\Element\Datastream[]
   */
  protected $datastreams = [];

  /**
   * {@inheritdoc}
   */
  protected function pop() {
    $old = parent::pop();

    if ($old instanceof ObjectProperties) {
      if ($this->properties === NULL) {
        $this->properties = $old;
      }
      else {
        throw new \Exception('Too many "objectProperties" elements.');
      }
    }
    elseif ($old instanceof Datastream) {
      $this[$old->id()] = $old;
    }

    return $old;
  }

  /**
   * Check if the given object property or element attribute is set.
   *
   * @param string $prop
   *   The item to check.
   *
   * @return bool
   *   TRUE if it is present; otherwise, FALSE.
   */
  public function __isset($prop) {
    return isset($this->properties[$prop]) || parent::__isset($prop);
  }

  /**
   * Get the object property or element attribute.
   *
   * @param string $prop
   *   The item to fetch.
   *
   * @return mixed
   *   The value of the item.
   */
  public function __get($prop) {
    return isset($this->properties[$prop]) ?
      $this->properties[$prop]->value() :
      parent::__get($prop);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'properties',
      'datastreams',
    ]);
  }

  /**
   * Accessors for the datastreams.
   *
   * @return \Drupal\dgi_migrate\Utility\Fedora3\Element\Datastream[]
   *   The associative array of datastream, keyed by datastream ID.
   */
  public function datastreams() {
    return $this->datastreams;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return isset($this->datastreams[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return $this->datastreams[$offset];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if (!isset($this[$offset])) {
      $this->datastreams[$offset] = $value;
    }
    else {
      throw new \Exception("Refusing to replace {$offset}.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    throw new \Exception('Not implemented.');
  }

  /**
   * DOMDocument instance for RELS-EXT, lazily-instantiated.
   *
   * @var \DOMDocument
   */
  protected $dom;

  /**
   * DOMXPath instance for RELS-EXT, lazily-instantiated.
   *
   * @var \DOMXPath
   */
  protected $xpath;

  /**
   * Get a DOMXPath instance for the RELS-EXT.
   *
   * @return \DOMXPath
   *   A DOMXPath instance with the RELS-EXT loaded and queryable.
   */
  protected function xpath() {
    if (!isset($this->xpath)) {
      $this->dom = new \DOMDocument();
      $this->dom->load($this['RELS-EXT']->getUri());
      $this->xpath = new \DOMXPath($this->dom);
      $ns = [
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'fre' => 'info:fedora/fedora-system:def/relations-external#',
        'fm' => 'info:fedora/fedora-system:def/model#',
        'isl' => 'http://islandora.ca/ontology/relsext#',
      ];
      foreach ($ns as $prefix => $uri) {
        $this->xpath->registerNamespace($prefix, $uri);
      }
    }

    return $this->xpath;
  }

  /**
   * Perform an xpath query for a resource in the RELS-EXT.
   *
   * @param string $query
   *   The query to perform.
   *
   * @return string[]
   *   The results of the query.
   */
  protected function relsExtResourceQuery($query) {
    $results = [];

    foreach ($this->xpath()->query($query) as $node) {
      $results[] = $node->nodeValue;
    }

    return $results;
  }

  /**
   * Get the models for the given object.
   *
   * @return string[]
   *   The content model PIDs of the given object.
   */
  public function models() {
    return $this->relsExtResourceQuery('/rdf:RDF/rdf:Description/fm:hasModel/@rdf:resource');
  }

  /**
   * Gets the sequence number for the given object.
   *
   * @return int|null
   *   The sequence number of the given object, or NULL.
   */
  public function sequenceNumber() {
    $sequences = $this->relsExtResourceQuery('/rdf:RDF/rdf:Description/isl:isSequenceNumber');
    $first = reset($sequences);
    return $first ? (int) $first : NULL;
  }

  /**
   * Get the parents of the given object.
   *
   * @return string[]
   *   The collection parents of the given object.
   */
  public function parents() {
    return $this->relsExtResourceQuery('/rdf:RDF/rdf:Description/*[self::fre:isMemberOfCollection or self::fre:isMemberOf]/@rdf:resource');
  }

}
