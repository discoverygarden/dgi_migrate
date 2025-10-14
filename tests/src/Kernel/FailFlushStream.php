<?php

namespace Drupal\Tests\dgi_migrate\Kernel;

use Drupal\Core\StreamWrapper\TemporaryStream;

/**
 * Stream wrapper returning a failure on stream flush.
 */
class FailFlushStream extends TemporaryStream {

  /**
   * {@inheritDoc}
   *
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function stream_flush() {
    parent::stream_flush();
    return FALSE;
  }

}
