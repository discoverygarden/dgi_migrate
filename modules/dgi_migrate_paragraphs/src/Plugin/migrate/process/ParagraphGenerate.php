<?php

namespace Drupal\dgi_migrate_paragraphs\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Generate Paragraph entities.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_paragraph_generate"
 * )
 *
 * @code
 * field_paragraphs:
 *   - plugin: dgi_paragraph_generate
 *     type: paragraph_bundle_type
 *     values:
 *       field_one: col_one
 *       field_two: col_two
 *       field_three: "@something_built"
 * field_paragraphs_processed:
 *   - plugin: dgi_paragraph_generate
 *     type: paragraph_bundle_type
 *     process_values: true
 *     values:
 *       field_one:
 *         - plugin: some_process_plugin
 *           plugin_property: sure_why_not
 *       field_two:
 *         - plugin: get
 *           source: '@some_built_thing'
 * @endcode
 */
class ParagraphGenerate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    assert(!empty($this->configuration['type']));
    assert(!empty($this->configuration['values']));

    if (!empty($this->configuration['process_values']) && $this->configuration['process_values']) {
      try {
        $extra_values = $this->processValues($value, $migrate_executable, $row);
      }
      catch (MigrateSkipRowException) {
        return [
          'target_id' => NULL,
          'target_revision_id' => NULL,
        ];
      }
    }
    else {
      $extra_values = $this->mapValues($migrate_executable, $row);
    }

    $paragraph = Paragraph::create(
      [
        'type' => $this->configuration['type'],
      ] +
      $extra_values
    );
    $paragraph->save();

    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

  /**
   * Map requested fields.
   *
   * @param \Drupal\migrate\MigrateExecutableInterface $executable
   *   The migration exectuable.
   * @param \Drupal\migrate\Row $row
   *   The row object being processed.
   *
   * @return array
   *   An associative array with the mapped values.
   */
  protected function mapValues(MigrateExecutableInterface $executable, Row $row) {
    $mapped = [];

    foreach ($this->configuration['values'] as $key => $property) {
      $mapped[$key] = $row->get($property);
    }

    return $mapped;
  }

  /**
   * Process requested values.
   *
   * @param mixed $value
   *   The source value for the plugin.
   * @param \Drupal\migrate\MigrateExecutableInterface $executable
   *   The migration exectuable.
   * @param \Drupal\migrate\Row $row
   *   The row object being processed.
   *
   * @return array
   *   An associative array of processed configuration values.
   */
  protected function processValues($value, MigrateExecutableInterface $executable, Row $row) {
    $mapped = [];

    foreach ($this->configuration['values'] as $key => $property) {
      try {
        $new_row = new Row($property);
        $executable->processRow($new_row, NULL, $value);
        $mapped[$key] = $new_row->getDestination();
      }
      catch (MigrateSkipProcessException $e) {
        $mapped[$key] = NULL;
      }
    }

    return $mapped;
  }

}
