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
    $this->updateEnd();
  }

  public function getUri() {
    return Substream::format($this->target, $this->start(), $this->length());
  }
}
