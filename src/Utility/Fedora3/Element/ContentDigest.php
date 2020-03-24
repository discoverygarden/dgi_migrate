<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class ContentDigest extends AbstractParser {

  use LeafTrait;
  use EmptyTrait;

  const TAG = 'foxml:contentDigest';

  public function id() {
    return $this->TYPE;
  }

  public function value() {
    return $this->DIGEST;
  }

}
