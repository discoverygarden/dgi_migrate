<?php

namespace Drupal\dgi_migrate_paragraphs\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
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
 * @endcode
 */
class ParagraphGenerate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    assert(!empty($this->configuration['type']));
    assert(!empty($this->configuration['values']));

    $paragraph = Paragraph::create(
      [
        'type' => $this->configuration['type'],
      ] +
      $this->mapValues($migrate_executable, $row)
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

}
