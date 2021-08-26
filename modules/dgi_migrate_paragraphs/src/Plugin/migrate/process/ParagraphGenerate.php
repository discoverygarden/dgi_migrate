<?php

namespace Drupal\dgi_migrate_paragraphs\Plugin\migrate\process;

use Drupal\dgi_migrate\Plugin\migrate\process\SubProcess;
use Drupal\migrate\MigrateExecutableInterface;
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
 * - validate: A boolean flag indicating whether the contents of the paragraph
 *   should be validated; defaults to FALSE.
 * - process_values: A boolean flag indicating whether values should be mapped
 *   directly from the current row (false, the default), or if we should kick
 *   of something of a subprocess flow, with nested process plugin
 *   configurations.
 * - propagate_skip: A boolean indicating how a "MigrateSkipRowException" should
 *   be handled when processing a specific paragraph entity. TRUE to also skip
 *   import of the parent entity; otherwise, FALSE to skip only those sub-
 *   entities throwing the exception. Defaults to TRUE.
 * - parent_row_key: A string representing a key under which to expose the
 *   the contents of the row to subprocessing with process_values. Defaults to
 *   "parent_row". The contents of the row are split into two keys "source" and
 *   "dest", containing respectively the source and (current) destination values
 *   of the parent row.
 * - parent_value_key: A string representing a key under which to expose the
 *   value received by the "dgi_paragraph_generate" plugin itself, to make it
 *   available to subprocessing. Defaults to "parent_value".
 */
class ParagraphGenerate extends SubProcess {

  /**
   * The type of paragraph to generate.
   *
   * @var string
   */
  protected $paragraphType;

  /**
   * Flag, indicating if the paragraph generated should be validated.
   *
   * @var bool
   */
  protected $validate;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    assert(!empty($this->configuration['type']));
    $this->paragraphType = $this->configuration['type'];
    $this->validate = $this->configuration['validate'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $paragraph = Paragraph::create(
      [
        'type' => $this->paragraphType,
      ] +
      parent::transform($value, $migrate_executable, $row, $destination_property)
    );

    $paragraph->setValidationRequired($this->validate);

    if ($this->validate) {
      try {
        $errors = $paragraph->validate();
      }
      catch (\Exception $e) {
        throw new \Exception(strtr('Encountered exception when validating :property.', [
          ':property' => $destination_property,
        ]), 0, $e);
      }
      if ($errors->count() > 0) {
        throw new MigrateSkipRowException(strtr('Paragraph property :property of type :type validation error(s): :errors', [
          ':property' => $destination_property,
          ':type' => $this->configuration['type'],
          ':errors' => $errors,
        ]));
      }
    }

    $paragraph->save();

    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

}
