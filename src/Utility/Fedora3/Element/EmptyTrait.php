<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

trait EmptyTrait {
  public function characters($parser, $characters) {
    if (strlen($characters) > 0) {
      throw new Exception('Node expected to be empty contained characters.');
    }
  }
}
