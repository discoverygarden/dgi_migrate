<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

/**
 * Interface for XML parsing.
 */
interface ParserInterface {

  /**
   * XML parser element start callback.
   *
   * @param resource $parser
   *   The active parser.
   * @param string $tag
   *   The tag opening.
   * @param string[] $attributes
   *   The attributes present in the opening tag.
   */
  public function tagOpen($parser, $tag, array $attributes);

  /**
   * XML parser element end callback.
   *
   * @param resource $parser
   *   The active parser.
   * @param string $tag
   *   The tag closing.
   */
  public function tagClose($parser, $tag);

  /**
   * XML parser character data callback.
   *
   * @param resource $parser
   *   The active parser.
   * @param string $chars
   *   The characters received.
   */
  public function characters($parser, $chars);

}
