<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

/**
 * Element handler for foxml:contentDigest.
 */
class ContentDigest extends AbstractParser {

  use LeafTrait;
  use EmptyTrait;

  const TAG = 'foxml:contentDigest';

  /**
   * Accessor for the ID/hash algorithm.
   *
   * @return string
   *   The hash algorithm represented by the digest.
   */
  public function id() {
    return $this->TYPE;
  }

  /**
   * Accessor for the hash/digest.
   *
   * @return string
   *   The stored hash/digest.
   */
  public function value() {
    return $this->DIGEST;
  }

}
