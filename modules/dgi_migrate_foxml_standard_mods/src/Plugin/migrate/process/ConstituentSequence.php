<?php

namespace Drupal\dgi_migrate_foxml_standard_mods\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Scrape the constituent sequence number from a compound constituent.
 *
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

  /**
   * Helper; escape PIDs as they were in I7.
   *
   * @param string $pid
   *   The PID to escape.
   *
   * @return string
   *   The escaped PID.
   *
   * @see https://github.com/Islandora/islandora_solution_pack_compound/blob/2fc713e0d0c16a0288353e958dfbe1500de3f244/includes/manage.form.inc#L389
   * @see https://github.com/Islandora/islandora_solution_pack_compound/blob/2fc713e0d0c16a0288353e958dfbe1500de3f244/includes/manage.form.inc#L437
   * @see https://github.com/Islandora/islandora_solution_pack_compound/blob/2fc713e0d0c16a0288353e958dfbe1500de3f244/includes/manage.form.inc#L468
   */
  protected function escapePid($pid) {
    return strtr($pid, [
      ':' => '_',
    ]);
  }

}
