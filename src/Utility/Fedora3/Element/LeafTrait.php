<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

/**
 * AbstractParser helper trait used for elements which should not have children.
 */
trait LeafTrait {

  /**
   * Tag opening handler.
   *
   * @param resource $parser
   *   The parsing parser.
   * @param string $tag
   *   The tag name.
   * @param string[] $attributes
   *   An associative array of attributes present on the given tag.
   *
   * @throws \Exception
   *   Encountered something invalid.
   */
  public function tagOpen($parser, $tag, array $attributes) {
    throw new \Exception(strtr('Leaf node should not contain additional elements; got a "!name" tag.', [
      '!name' => $tag,
    ]));
  }

  /**
   * Tag closing handler.
   *
   * @param resource $parser
   *   The parsing parser.
   * @param string $tag
   *   The tag name.
   *
   * @throws \Exception
   *   Encountered something invalid.
   */
  public function tagClose($parser, $tag) {
    throw new \Exception(strtr('Leaf node should not contain additional elements; got a "!name" tag.', [
      '!name' => $tag,
    ]));
  }

}
