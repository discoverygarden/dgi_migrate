<?php

namespace Drupal\dgi_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * @MigrateSource(
 *   id = "dgi_migration"
 * )
 */
class Migration extends SourcePluginBase {
  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    return \ArrayIterator
  }

}
