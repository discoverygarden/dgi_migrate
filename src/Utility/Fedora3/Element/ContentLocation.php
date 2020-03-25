<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class ContentLocation extends AbstractParser {

  use LeafTrait;
  use EmptyTrait;

  const TAG = 'foxml:contentLocation';

  public function getUri() {
    if ($this->TYPE === 'URL') {
      return $this->REF;
    }
    else {
      // XXX: An internal URI would require additional dereferencing to be
      // useful.
      throw new Exception('Refusing to provide internal URI.');
    }
  }
}
