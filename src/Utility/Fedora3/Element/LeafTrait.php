<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

trait LeafTrait {
  public function tagOpen($parser, $tag, $attributes) {
    throw new Exception('Leaf node should not contain additional elements.');
  }
  public function tagClose($parser, $tag) {
    throw new Exception('Leaf node should not contain additional elements.');
  }
}
