<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

abstract class AbstractParser implements ParserInterface {
  const MAP = [];

  protected $map = NULL;
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
  public function __sleep() {
    return ['attributes'];
  }

  public function close() {
    // No-op by default.
  }

  protected function map() {
    if ($this->map === NULL) {
      $this->map = array_combine(
        array_map(function ($key) {
          list($prefix, $name) = explode(':', $key);
          if ($prefix === 'foxml') {
            $key = "info:fedora/fedora-system:def/foxml#:{$name}";
          }
          return $key;
        }, array_keys(static::MAP)),
        static::MAP
      );
    }
    return $this->map;
  }

  /**
   * {@inheritdoc}
   */
  public function tagOpen($parser, $tag, $attributes) {
    if (isset($this->map()[$tag])) {
      if (!isset($this->depths[$tag])) {
        $this->depths[$tag] = 1;
      }
      else {
        $this->depths[$tag]++;
      }
      if ($this->depths[$tag] === 1) {
        $class = $this->map()[$tag];
        $this->push(new $class($this->foxmlParser, $attributes));
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
    if (isset($this->map()[$tag])) {
      $this->depths[$tag]--;
      if ($this->depths[$tag] === 0) {
        $this->pop();
        unset($this->depths[$tag]);
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
    if ($this->current()) {
      $this->current()->characters($parser, $chars);
    }
    else {
      // XXX: Characters are suppress.
    }
  }
}
