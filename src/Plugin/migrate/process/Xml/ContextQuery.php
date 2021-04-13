<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Query a DOMXPath using a DOMNode context as the source.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.xml.context_query"
 * )
 *
 * @code
 * sub_context_values:
 *   plugin: dgi_migrate.process.xml.context_query
 *   source: '@some_dom_node'
 *   xpath: '@some_dom_xpath'
 *   query: '/my/query'
 * @endcode
 */
class ContextQuery extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    assert(!empty($this->configuration['xpath']));
    assert(!empty($this->configuration['query']));
    $xpath = $row->get($this->configuration['xpath']);
    if (!($xpath instanceof \DOMXPath)) {
      throw new MigrateException('Requires an "xpath" parameter that is an instance of DOMXPath');
    }
    if (!($value instanceof \DOMNode)) {
      throw new MigrateException('Input should be a DOMNode.');
    }

    return $xpath->query($this->configuration['query'], $value);
  }

}
