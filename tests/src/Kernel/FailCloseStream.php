<?php

namespace Drupal\Tests\dgi_migrate\Kernel;

use Drupal\Core\StreamWrapper\TemporaryStream;

/**
 * Stream wrapper attempting to return a failure on stream closing.
 */
class FailCloseStream extends TemporaryStream {

  /**
   * {@inheritDoc}
   *
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function stream_close() {
    parent::stream_close();
    return FALSE;
  }

}
