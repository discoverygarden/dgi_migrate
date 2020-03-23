<?php

namespace Drupal\dgi_migrate\Plugin\migrate\source;

use Drupal\dgi_migrate\Utility\Fedora3Sparql;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * @MigrateSource(
 *   id = "fedora_3_via_sparql"
 * )
 */
class Fedora3Sparql extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    return new Fedora3Sparql(
      $this->configuration['url'],
      $this->configuration['user'],
      $this->configuration['pass']
    );
  }

}
