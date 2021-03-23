<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Query a DOMXpath using a DOMNode context as the source.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.context_query"
 * )
 *
 * @code
 * sub_context_values:
 *   plugin: dgi_migrate.process.context_query
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
    assert(!empty($this->configuration['xpath']))
    assert(!empty($this->configuration['query']))
    if (!($this->configuration['xpath'] instanceof \DOMXpath)) {
      throw new MigrateException('Requires an "xpath" parameter');
    }
    if (!($value instanceof \DOMNode)) {
      throw new MigrateException('Input should be a DOMNode.');
    }

    return $this->configuration['xpath']->query($this->configuration['query'], $value);
  }
}