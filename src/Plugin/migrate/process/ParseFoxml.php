<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\foxml\Plugin\migrate\process\Parse;
use Drupal\foxml\Utility\Fedora3\FoxmlParser;

/**
 * Parse FOXML.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.parse_foxml"
 * )
 *
 * @deprecated in %deprecation-version% and is removed from %removal-version%
 * @see https://github.com/discoverygarden/dgi_migrate/pull/21
 */
class ParseFoxml extends Parse {

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FoxmlParser $parser) {
    @trigger_error('\Drupal\dgi_migrate\Plugin\migrate\process\ParseFoxml is deprecated in %deprecation-version%; use \Drupal\foxml\Plugin\migrate\process\Parse before its removal in %removal-version%.', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $parser);
  }

}
