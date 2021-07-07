<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process\Xml;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\dgi_migrate\Plugin\migrate\process\MissingBehaviorTrait;
use EDTF\EdtfFactory;

/**
 * Assemble an EDTF date or date range by querying dates in XML.
 *
 * Requires a DOMNode as input, and an 'xpath' DOMXpath property to run the
 * querying.
 *
 * At least one of three xpath queries need to be provided:
 * - 'single_date' queries for an individual date
 * - 'range_start' queries for the start of a date range
 * - 'range_end' queries for the end of a date range
 *
 * The output is handled thus:
 * - If a range_start, or a range_end, or both, are found via XPath query, an
 *   EDTF date range will be assembled, and any results from single_date will be
 *   ignored. Both found values will be validated against the EDTF standard so
 *   that an invalid date range is not assembled.
 * - If neither a range_start nor a range_end are found via XPath query but the
 *   single_date query returns a value, it will be validated against the EDTF
 *   standard and returned.
 * - If no query finds a value, null will be returned.
 *
 * Use the 'indicate_open' flag to indicate that a missing part of a found range
 * should use the EDTF 'open' range indicator, i.e., "..". Default behaviour is
 * 'false', which will use an empty string for missing parts of the range.
 *
 * Example:
 * @code
 * process:
 *   - plugin: dgi_migrate.xml.assemble_edtf_date_range
 *     source: some_domnode
 *     xpath: some_domxpath
 *     single_date: 'path/to/a/single/date'
 *     range_start: 'path/to/a/date/range[@start]'
 *     range_end: 'path/to/a/date/range[@end]'
 *     indicate_open: false
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.xml.assemble_edtf_date_range"
 * )
 */
class AssembleEDTFDateRange extends ProcessPluginBase {

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
    parent::construct($configuration, $plugin_id, $plugin_definition);
    $this->missingBehaviorInit();
    $this->validator = EdtfFactory::newValidator();
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Assertions: We need an 'xpath' property, it should be a DOMXpath object,
    // the $value should be a DOMNode object, and we should have at least one
    // date query to look for.
    assert(!empty($this->configuration['xpath']));
    $xpath = $row->get($this->configuration['xpath']);
    if (!($xpath instanceof \DOMXpath)) {
      throw $this->getMissingException(strtr('Requires an "xpath" parameter that is an instance of DOMXpath for :property.', [
        ':property' => $destination_property,
      ]));
    }
    if (!($value instanceof \DOMNode)) {
      throw $this->getMissingException(strtr('Input should be a DOMNode for :property.', [
        ':property' => $destination_property,
      ]));
    }
    if (empty($this->configuration['single_date']) && empty($this->configuration['range_start']) && empty($this->configuration['range_end'])) {
      throw $this->getMissingException(strtr('Requires at least one of the three parameters "single_date", "range_start", or "range_end" to be provided for :property.', [
        ':property' => $destination_property,
      ]));
    }

    $this->missing = (isset($this->configuration['indicate_open']) && isset($this->configuration['indicate_open'])) ? '..' : '';
    $return_value = $this->getEdtfDateRange($xpath, $value);
    if (!$return_value) {
      $return_value = $this->getSingleDate($xpath, $value);
    }
    return $return_value;
  }

  /**
   * Gets a validated EDTF date range using XPath queries.
   *
   * @param \DOMXpath $xpath
   *   The XPath object to use.
   * @param \DOMNode $node
   *   The reference node to query from.
   *
   * @return string|null
   *   An EDTF date range string, or NULL if the start and end could not be
   *   found.
   */
  protected function getEdtfDateRange(DOMXpath $xpath, DOMNode $node) {
    if (empty($this->configuration['range_start']) && empty($this->configuration['range_end'])) {
      return NULL;
    }

    $range_start = empty($this->configuration['range_start']) ? $this->missing : $this->validatedQuery($xpath, $this->configuration['range_start'], $node);
    $range_end = empty($this->configuration['range_end']) ? $this->missing : $this->validatedQuery($xpath, $this->configuration['range_end'], $node);

    if ($range_start === $this->missing && $range_end === $this->missing) {
      return NULL;
    }

    return "{$start}/{$end}";
  }

  /**
   * Gets a validated EDTF individual date using an XPath query.
   *
   * @param \DOMXpath $xpath
   *   The XPath object to use.
   * @param \DOMNode $node
   *   The reference node to query from.
   *
   * @return string|null
   *   An EDTF date string, or NULL if one could not be found.
   */
  protected function getSingleDate(DOMXpath $xpath, DOMNode $node) {
    if (empty($this->configuration['single_date'])) {
      return NULL;
    }
    return $this->validatedQuery($xpath, $this->configuration['single_date'], $node);
  }

  /**
   * Searches for an EDTF date using the given query on the given node.
   *
   * The first string found will be accepted as the date, and will be validated
   * as EDTF.
   *
   * @param DOMXpath $xpath
   *   A DOMXpath object to use for the query.
   * @param string $query
   *   The query to run.
   * @param DOMNode $node
   *   The DOMNode to run the query from.
   *
   * @return string|null
   *   The first string returned from the query, or NULL for no results.
   */
  protected function validatedQuery(DOMXpath $xpath, $query, DOMNode $node) {
    $result = $xpath->query($query, $node);
    if (!$result->length) {
      return NULL;
    }
    $date = $result->item(0)->nodeValue;
    if (!$this->validator->isValidEdtf($date)) {
      throw new \InvalidArgumentException(strtr('Could not validate ":date" as an EDTF date while querying :query.', [
        ':date' => $date,
        ':query' => $query,
      ]));
    }
    return $date;
  }

}
