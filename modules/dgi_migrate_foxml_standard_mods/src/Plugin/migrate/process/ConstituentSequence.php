<?php

namespace Drupal\dgi_migrate_foxml_standard_mods\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate_foxml_standard_mods.constituent_sequence"
 * )
 */
class ConstituentSequence extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $xpath = $row->get($this->configuration['xpath']);
    $node = $row->get($this->configuration['node']);

    $output = [];
    foreach ((array) $value as $pid) {
      $output[] = $xpath->evaluate("string(islandora-rels-ext:isSequenceNumberOf{$this->escapePid($pid)})", $node);
    }

    $output = array_map('trim', $output);
    $output = array_filter($output);

    return is_array($value) ? $output : reset($output);
  }

  protected function escapePid($pid) {
    return strtr($pid, [
      ':' => '_',
    ]);
  }

}
