<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

/**
 * Element handler for foxml:property.
 */
class ObjectProperty extends AbstractParser {

  use LeafTrait;
  use EmptyTrait;

  const TAG = 'foxml:property';

  /**
   * Fetch the name of the given property.
   *
   * @return string
   *   The name of the property.
   */
  public function id() {
    $parts = explode('#', $this->attributes['NAME']);
    return $parts[1];
  }

  /**
   * Fetch the value of the property.
   *
   * @return string
   *   The value of the property.
   */
  public function value() {
    return $this->attributes['VALUE'];
  }

}
