<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\dgi_migrate\Plugin\migrate\process\MissingBehaviorTrait;

/**
 * Assemble a date or date range using some EDTF standard pieces.
 *
 * At least one of three properties need to be provided to the 'source':
 * - 'single_date' for an individual date
 * - 'range_start' for the start of a date range
 * - 'range_end' for the end of a date range
 *
 * The output is handled thus:
 * - If a range_start, or a range_end, or both, are provided and not empty, an
 *   EDTF-style date range will be assembled, and any results from single_date
 *   will be ignored.
 * - If neither a range_start nor a range_end are provided or are empty, but the
 *   single_date is provided and has a value, it is returned.
 * - If no provided property has a value, null will be returned.
 *
 * Use the 'indicate_open' flag to indicate that a missing part of a found range
 * should use the EDTF 'open' range indicator, i.e., "..". Default behaviour is
 * 'false', which will use an empty string for missing parts of the range.
 *
 * N.B. Returned values are NOT validated against the EDTF standard. Use the
 * dgi_migrate_edtf_validator module to validate output.
 *
 * Example:
 * @code
 * process:
 *   - plugin: dgi_migrate.process.assemble_date
 *     source:
 *       single_date: some_single_date
 *       range_start: some_range_start
 *       range_end: some_range_end
 *     indicate_open: false
 *   - plugin: dgi_migrate_edtf_validator
 *     intervals: true
 *     strict: true
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.assemble_date"
 * )
 */
class AssembleDate extends ProcessPluginBase {

  use MissingBehaviorTrait;

  /**
   * EDTF validator.
   *
   * @var \EDTF\EdtfValidator
   */
  protected $validator;

  /**
   * The string to use for a missing part of a range.
   *
   * @var string
   */
  protected $missing;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->missingBehaviorInit();
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($value['single_date']) && !isset($value['range_start']) && !isset($value['range_end'])) {
      throw $this->getMissingException(strtr('Requires at least one of the three keys in the source, "single_date", "range_start", or "range_end" to be provided for :property.', [
        ':property' => $destination_property,
      ]));
    }

    $this->missing = (isset($this->configuration['indicate_open']) && isset($this->configuration['indicate_open'])) ? '..' : '';
    $return_value = $this->getDateRange($value);
    if (!$return_value) {
      $return_value = isset($value['single_date']) ? $value['single_date'] : NULL;
    }
    return $return_value;
  }

  /**
   * Gets a date range for the input.
   *
   * @param array $parts
   *   The parts of the date range from the input source.
   *
   * @return string|null
   *   A date range string, or NULL if the start and end could not be
   *   found.
   */
  protected function getDateRange(array $parts) {
    if (!isset($parts['range_start']) && !isset($parts['range_end'])) {
      return NULL;
    }

    $range_start = !isset($parts['range_start']) ? $this->missing : $parts['range_start'];
    $range_end = !isset($parts['range_end']) ? $this->missing : $parts['range_end'];

    if ($range_start === $this->missing && $range_end === $this->missing) {
      return NULL;
    }

    return "{$start}/{$end}";
  }

}
