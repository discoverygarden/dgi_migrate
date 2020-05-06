<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

/**
 * Element handler for foxml:datastreamVersion.
 */
class DatastreamVersion extends AbstractParser {
  const TAG = 'foxml:datastreamVersion';
  const MAP = [
    XmlContent::TAG => XmlContent::class,
    BinaryContent::TAG => BinaryContent::class,
    ContentLocation::TAG => ContentLocation::class,
    ContentDigest::TAG => ContentDigest::class,
  ];

  /**
   * An associative array mapping hash algorithms to ContentDigest instances.
   *
   * @var \Drupal\dgi_migrate\Utility\Fedora3\Element\ContentDigest[]
   */
  protected $digests = [];

  /**
   * The content of the given datastream version.
   *
   * @var \Drupal\dgi_migrate\Utility\Fedora3\Element\ContentLocation|\Drupal\dgi_migrate\Utility\Fedora3\Element\AbstractStreamOffsetContent
   */
  protected $content = NULL;

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'digests',
      'content',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function close() {
    parent::close();

    if (!empty($this->digests) && $this->content instanceof BinaryContent) {
      foreach ($this->digests as $algo => $el) {
        assert(hash_file(str_replace('-', '', strtolower($algo)), $this->content->getUri(), FALSE) === strtolower($el->value()), 'hash matches for ' . $this->content->getUri());
      }
    }
  }

  /**
   * ID accessor.
   *
   * @return string
   *   The ID of this datastream version.
   */
  public function id() {
    return $this->ID;
  }

  /**
   * Content accessor.
   *
   * @return \Drupal\dgi_migrate\Utility\Fedora3\Element\ContentLocation|\Drupal\dgi_migrate\Utility\Fedora3\Element\AbstractStreamOffsetContent
   *   The underlying content element.
   */
  public function content() {
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function pop() {
    $old = parent::pop();

    if ($old instanceof ContentDigest) {
      if (!isset($this->digests[$old->id()])) {
        $this->digests[$old->id()] = $old;
      }
      else {
        throw new Exception("Avoiding replacing {$old->id()} hash entry.");
      }
    }
    elseif ($this->content === NULL) {
      $this->content = $old;
    }
    else {
      throw new Exception('Avoiding replacing content.');
    }

    return $old;
  }

  /**
   * Helper; get the URI from the underlying content.
   *
   * @return string
   *   The URI.
   */
  public function getUri() {
    return $this->content->getUri();
  }

}
