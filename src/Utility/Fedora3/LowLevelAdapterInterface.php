<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

interface LowLevelAdapterInterface {

  /**
   * Dereference an ID from the given storage.
   *
   * @param string $id
   *   The ID to dereference.
   *
   * @return string
   *   A URI/path to the given resource.
   */
  public function dereference($id);

}
