<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;

/**
 * Generates entities within the process plugin with no performed lookup.
 *
 * As no lookup is performed, the value_key is not required. The bundle,
 * bundle_key and entity_type are, however.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.entity_generate_forced"
 * )
 *
 * @see EntityGenerate
 */
class EntityGenerateForced extends EntityGenerate {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $this->row = $row;
    $this->migrateExecutable = $migrateExecutable;
    $this->lookupBundle = $this->configuration['bundle'];
    $this->lookupBundleKey = $this->configuration['bundle_key'];
    $this->lookupEntityType = $this->configuration['entity_type'];
    return $this->generateEntity($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function entity($value) {
    $entity_values = [$this->lookupValueKey => $value];

    if ($this->lookupBundleKey) {
      $entity_values[$this->lookupBundleKey] = $this->lookupBundle;
    }

    // Gather any static default values for properties/fields.
    if (isset($this->configuration['default_values']) && is_array($this->configuration['default_values'])) {
      foreach ($this->configuration['default_values'] as $key => $value) {
        $entity_values[$key] = $value;
      }
    }
    // Gather any additional properties/fields.
    if (isset($this->configuration['values']) && is_array($this->configuration['values'])) {
      $source_values = $this->getProcessPlugin->transform(NULL, $this->migrateExecutable, $this->row, '');
      foreach ($this->configuration['values'] as $key => $property) {
        $source_value = $this->getProcessPlugin->transform(NULL, $this->migrateExecutable, $this->row, $property);
        $position_value = reset($source_values);
        NestedArray::setValue($entity_values, explode(Row::PROPERTY_SEPARATOR, $key), $position_value, TRUE);
      }
    }

    return $entity_values;
  }

}
