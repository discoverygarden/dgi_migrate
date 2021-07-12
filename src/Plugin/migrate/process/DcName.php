<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Accesses a property from an object.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.dc_name"
 * )
 */
class DcName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->map = $this->configuration['map'];
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $parts = [];

    if (isset($this->map['untyped']) && ($untyped = $row->get($this->map['untyped']))) {
      $parts[] = "$untyped ";
    }
    if (isset($this->map['family']) && ($family = $row->get($this->map['family']))) {
      $parts[] = $family;
    }
    if (isset($this->map['given']) && ($given = $row->get($this->map['given']))) {
      $parts[] = ", $given";
    }
    if (isset($this->map['date']) && ($date = $row->get($this->map['date']))) {
      $parts[] = ", $date";
    }
    if (isset($this->map['display_form']) && ($display_form = $row->get($this->map['display_form']))) {
      $parts[] = " ($display_form)";
    }

    return empty($parts) ? NULL : trim(implode('', $parts));

  }

}
