<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class ObjectProperty extends AbstractParser {

  use LeafTrait;
  use EmptyTrait;

  const TAG = 'foxml:objectProperty';

  public function id() {
    $parts = explode('#', $this->attributes['NAME']);
    return $parts[1];
  }

  public function value() {
    return $this->attributes['VALUE'];
  }
}
