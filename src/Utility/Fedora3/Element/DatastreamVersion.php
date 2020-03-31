<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class DatastreamVersion extends AbstractParser {
  const TAG = 'foxml:datastreamVersion';
  const MAP = [
    XmlContent::TAG => XmlContent::class,
    BinaryContent::TAG => BinaryContent::class,
    ContentLocation::TAG => ContentLocation::class,
    ContentDigest::TAG => ContentDigest::class,
  ];

  protected $digests = [];
  protected $content = NULL;

  public function id() {
    return $this->ID;
  }
  public function content() {
    return $this->content;
  }
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

  public function getUri() {
    return $this->content->getUri();
  }
}
