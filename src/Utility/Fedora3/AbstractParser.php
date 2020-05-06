<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

/**
 * Abstract (FO)XML parser.
 */
abstract class AbstractParser implements ParserInterface {
  const MAP = [];

  /**
   * Memoized MAP with "foxml:" expanded to the full URI.
   *
   * @var string[]
   */
  protected $map = NULL;

  /**
   * The parser instance parsing.
   *
   * @var \Drupal\dgi_migrate\Utility\FoxmlParser
   */
  protected $foxmlParser;

  /**
   * Parser state for this element; the depths of each tag.
   *
   * @var int[]
   */
  protected $depths = [];

  /**
   * Parser state for this element; the stack of elements.
   *
   * @var \Drupal\dgi_migrate\Utility\Fedora3\ParserInterface[]
   */
  protected $stack = [];

  /**
   * Associative array of attributes on this element.
   *
   * @var string[]
   */
  protected $attributes;

  /**
   * Get the current element being processed.
   *
   * @return \Drupal\dgi_migrate\Utility\Fedora3\ParserInterface
   *   The current element being processed.
   */
  protected function current() {
    return end($this->stack);
  }

  /**
   * Push another element onto the stack being parsed.
   *
   * @param \Drupal\dgi_migrate\Utility\Fedora3\ParserInterface $new
   *   The new element.
   */
  protected function push(ParserInterface $new) {
    $this->stack[] = $new;
  }

  /**
   * Pop the finished element off of the stack.
   *
   * @return \Drupal\dgi_migrate\Utility\Fedora3\ParserInterface
   *   A fully parsed element.
   */
  protected function pop() {
    $old = array_pop($this->stack);
    $old->close();
    return $old;
  }

  /**
   * Constructor.
   *
   * @param \Drupal\dgi_migrate\Utility\FoxmlParser $foxml_parser
   *   The root parser parsing.
   * @param string[] $attributes
   *   The attributes for the given element.
   */
  public function __construct(FoxmlParser $foxml_parser, array $attributes) {
    $this->foxmlParser = $foxml_parser;
    $this->attributes = $attributes;
  }

  /**
   * Destructor; clean up.
   */
  public function __destruct() {
    $this->close();
  }

  /**
   * Get the root parser.
   *
   * @return \Drupal\dgi_migrate\Utility\FoxmlParser
   *   The root parser... essentially so XmlContent and BinaryContent can
   *   determine their offsets.
   */
  protected function getFoxmlParser() {
    return $this->foxmlParser;
  }

  /**
   * Get the target attribute.
   *
   * @return string
   *   The value of the attribute.
   */
  public function __get($offset) {
    return $this->attributes[$offset];
  }

  /**
   * Check if the given attribute exists.
   *
   * @return bool
   *   TRUE if it exists; otherwise, FALSE.
   */
  public function __isset($offset) {
    return isset($this->attributes[$offset]);
  }

  /**
   * Serialization descriptor.
   *
   * We do not care about trying to serialize everything on the object, so
   * let's specify a more restricted set of things.
   *
   * XXX: Ideally, we would have something separate from the parser classes
   * to represent the parsed structures; however... things are already together
   * ... could possibly separate the concerns of the parsing and the structure
   * in the future?
   *
   * @return string[]
   *   The object members to be serialized.
   */
  public function __sleep() {
    return ['attributes'];
  }

  /**
   * Clean up when the element is finished.
   */
  public function close() {
    // XXX: Attempt to avoid circular references, just in case.
    unset($this->foxmlParser);
  }

  /**
   * Get the map of tags to class names.
   *
   * @return string[]
   *   An associative array mapping the tag names provided by the parser to the
   *   names of the classes which should handle them.
   */
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
  public function tagOpen($parser, $tag, array $attributes) {
    if (isset($this->map()[$tag])) {
      if (!isset($this->depths[$tag])) {
        $this->depths[$tag] = 1;
      }
      else {
        $this->depths[$tag]++;
      }
      if ($this->depths[$tag] === 1) {
        $class = $this->map()[$tag];
        $this->push(new $class($this->getFoxmlParser(), $attributes));
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
      // XXX: Characters are suppressed.
    }
  }

}
