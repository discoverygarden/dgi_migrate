<?php

namespace Drupal\dgi_migrate\Utility\Fedora3

abstract class AbstractParser implements ParserInterface {
  const MAP = [];

  protected $foxmlParser;
  protected $depths = [];
  protected $stack = [];
  protected $attributes;

  protected function current() {
    return end($this->stack);
  }
  protected function push(ParserInterface $new) {
    $this->stack[] = $new;
  }
  protected function pop() {
    $old = array_pop($this->stack);
    $old->close();
    return $old;
  }

  public function __construct(FoxmlParser $foxml_parser, array $attributes) {
    $this->foxmlParser = $foxml_parser;
    $this->attributes = $attributes;
  }

  public function __get($offset) {
    return $this->attributes[$offset];
  }
  public function __isset($offset) {
    return isset($this->attributes[$offset]);
  }

  public function close() {
    // No-op by default.
  }

  /**
   * {@inheritdoc}
   */
  public function tagOpen($parser, $tag, $attributes) {
    if (isset(static::MAP[$tag]) {
      $this->depths[$tag]++;
      if ($this->depths[$tag] === 1) {
        $this->push(static::MAP[$tag]($this->foxmlParser, $attributes));
      }
      else {
        $this->current()->tagOpen($parser, $tag, $attributes);
      }
    }
    else {
      $this->current()->tagOpen($parser, $tag, $attributes);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tagClose($parser, $tag) {
    if (isset(static::MAP[$tag]) {
      $this->depths[$tag]--;
      if ($this->depths[$tag] === 0) {
        $this->pop();
      }
      else {
        $this->current()->tagClose($parser, $tag);
      }
    }
    else {
      $this->current()->tagClose($parser, $tag);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function characters($parser, $chars) {
    $this->current()->characters($parser, $chars);
  }
}
