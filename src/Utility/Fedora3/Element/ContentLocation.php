<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

/**
 * Element handler for foxml:contentLocation.
 */
class ContentLocation extends AbstractParser {

  use LeafTrait;
  use EmptyTrait;

  const TAG = 'foxml:contentLocation';

  /**
   * Helper to fetch the URI.
   *
   * @return string
   *   The URI if it's described as a URL.
   *
   * @throws \Exception
   *   If the location refers to an internal URI.
   */
  public function getUri() {
    if ($this->TYPE === 'URL') {
      return $this->REF;
    }
    else {
      // XXX: An internal URI would require additional dereferencing to be
      // useful.
      throw new \Exception('Refusing to provide internal URI.');
    }
  }

}
