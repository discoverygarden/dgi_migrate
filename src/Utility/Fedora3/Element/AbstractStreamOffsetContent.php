<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;
use Drupal\dgi_migrate\Utility\Substream;

/**
 * Abstract element handler for inline content.
 */
abstract class AbstractStreamOffsetContent extends AbstractParser {

  /**
   * The byte offset of the inline content in the target document.
   *
   * @var int
   */
  protected $start;

  /**
   * The byte offset of the end of the inline content in the target document.
   *
   * @var int
   */
  protected $end;

  /**
   * The URI/path of the target document.
   *
   * @var string
   */
  protected $target;

  /**
   * Constructor.
   */
  public function __construct($parser, $attributes) {
    parent::__construct($parser, $attributes);

    $this->target = $this->getFoxmlParser()->getTarget();
    // XXX: The "+ 1" is necessary to skip over the ">" at the end of the tag...
    $this->start = $this->getFoxmlParser()->getOffset() + 1;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'start',
      'end',
      'target',
    ]);
  }

  /**
   * Accessor for the starting offset.
   *
   * @return int
   *   The starting offset.
   */
  public function start() {
    return $this->start;
  }

  /**
   * Accessor for the ending offset.
   *
   * @return int
   *   The ending offset.
   */
  public function end() {
    return $this->end;
  }

  /**
   * Accessor for the length of the stream.
   *
   * @return int
   *   The length of the described substream.
   */
  public function length() {
    return $this->end - $this->start;
  }

  /**
   * Helper; update the ending offset.
   */
  protected function updateEnd() {
    $this->end = $this->getFoxmlParser()->getOffset();
  }

  /**
   * {@inheritdoc}
   */
  public function tagOpen($parser, $tag, array $attributes) {
    $this->updateEnd();
  }

  /**
   * {@inheritdoc}
   */
  public function tagClose($parser, $tag) {
    $this->updateEnd();
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return Substream::format($this->target, $this->start(), $this->length());
  }

}
