<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;
use Drupal\dgi_migrate\Utility\Fedora3\FoxmlParser;
use Drupal\dgi_migrate\Utility\Substream;

abstract class AbstractStreamOffsetContent extends AbstractParser {
  protected $start;
  protected $end;
  protected $target;

  public function __construct($parser, $attributes) {
    parent::__construct($parser, $attributes);

    $this->target = $this->getFoxmlParser()->getTarget();
    // XXX: The "+ 1" is necessary to skip over the ">" at the end of the tag...
    $this->start = $this->getFoxmlParser()->getOffset() + 1;
  }

  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'start',
      'end',
      'target',
    ]);
  }

  public function start() {
    return $this->start;
  }
  public function end() {
    return $this->end;
  }
  public function length() {
    return $this->end - $this->start;
  }
  protected function updateEnd() {
    $this->end = $this->getFoxmlParser()->getOffset();
  }

  public function tagOpen($parser, $tag, $attributes) {
    $this->updateEnd();
  }
  public function tagClose($parser, $tag) {
    $this->updateEnd();
  }
  public function characters($parser, $chars) {
    $offset = $this->getFoxmlParser()->getOffset();
    if ($offset === $this->end) {
      // XXX: If we encounter a chunk of XML which _only_ contains characters,
      // the reported offset is not changed, but we need to account for the
      // characters we were just given.
      $this->end += strlen($chars);
    }
    else {
      $this->end = $offset;
    }
  }

  public function getUri() {
    return Substream::format($this->target, $this->start(), $this->length());
  }
}
