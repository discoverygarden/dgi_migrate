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
 *     parent_row_key: parent_row
 *     parent_value_key: parent_value
 *     values:
 *       field_one:
 *         - plugin: some_process_plugin
 *           source: parent_value
 *           plugin_property: sure_why_not
 *       field_two:
 *         - plugin: get
 *           source: 'parent_row/dest/some_built_thing'
 * @endcode
 *
 * Configuration contents:
 * - type: The paragraph bundle with which to generate a paragraph.
 * - values: A mapping of values to use to create the paragraph. Exact contents
 *   vary based upon the "process_values" flag.
 * - process_values: A boolean flag indicating whether values should be mapped
 *   directly from the current row (false, the default), or if we should kick
 *   of something of a subprocess flow, with nested process plugin
 *   configurations.
 * - parent_row_key: A string representing a key under which to expose the
 *   the contents of the row to subprocessing with process_values. Defaults to
 *   "parent_row". The contents of the row are split into two keys "source" and
 *   "dest", containing respectively the source and (current) destination values
 *   of the parent row.
 * - parent_value_key: A string representing a key under which to expose the
 *   value received by the "dgi_paragraph_generate" plugin itself, to make it
 *   available to subprocessing. Defaults to "parent_value".
 */
class ParagraphGenerate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    assert(!empty($this->configuration['type']));
    assert(!empty($this->configuration['values']));

    if ($this->configuration['process_values'] ?? FALSE) {
      try {
        $extra_values = $this->processValues($value, $migrate_executable, $row);
      }
      catch (MigrateSkipRowException $e) {
        return NULL;
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
    $parent_row_key = $this->configuration['parent_row_key'] ?? 'parent_row';
    $parent_value_key = $this->configuration['parent_value_key'] ?? 'parent_value';

    $new_row = new Row([
      $parent_row_key => [
        'source' => $row->getSource(),
        'dest' => $row->getDestination(),
      ],
      $parent_value_key => $value,
    ]);

    $executable->processRow($new_row, $this->configuration['values']);

    return $new_row->getDestination();
  }

}
