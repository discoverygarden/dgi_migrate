<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

// XXX: A number of things related to deprecations are specific to things on
// drupal.org... so let's just suppress their stuff.
// pbpcs:disable Drupal.Commenting.Deprecated.*
use Drupal\foxml\Plugin\migrate\process\Parse;
use Drupal\foxml\Utility\Fedora3\FoxmlParser;

/**
 * Parse FOXML.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.parse_foxml"
 * )
 *
 * @deprecated as it was moved out to the "foxml" module.
 * @see https://github.com/discoverygarden/dgi_migrate/pull/21
 */
class ParseFoxml extends Parse {

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FoxmlParser $parser) {
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError.TriggerErrorTextLayoutRelaxed
    @trigger_error('\Drupal\dgi_migrate\Plugin\migrate\process\ParseFoxml is deprecated; use \Drupal\foxml\Plugin\migrate\process\Parse.', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $parser);
  }

}
