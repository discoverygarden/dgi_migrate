<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

/**
 * AbstractParser helper trait; used for elements which should be empty.
 */
trait EmptyTrait {

  /**
   * Character handler.
   *
   * @param resource $parser
   *   The parsing parser.
   * @param string $characters
   *   Characters being emitted from the parse.
   *
   * @throws \Exception
   *   Got characters when not expecting any.
   */
  public function characters($parser, $characters) {
    if (strlen($characters) > 0) {
      throw new \Exception('Node expected to be empty contained characters.');
    }
  }

}
