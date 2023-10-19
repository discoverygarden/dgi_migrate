<?php

namespace Drupal\dgi_migrate_foxml_standard_mods\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

use Drupal\Component\Utility\Unicode;

/**
 * Generate a Title paragraph.
 *
 * Mimics the generation of paragraphs in the LoC MODS to DC XSLT so that we
 * don't have to have a completely obtuse pile of process paragraphs trying to
 * accomplish the same thing.
 *
 * Expects a DOMNode as the source input.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_standard_title_paragraph"
 * )
 *
 * @code
 * field_title:
 *   plugin: dgi_standard_title_paragraph
 *   source: '@some_domnode'
 * @endcode
 */
class DgiStandardTitleParagraph extends ProcessPluginBase {

  /**
   * Whether entities generated should be validated.
   *
   * @var bool
   */
  protected bool $validate = FALSE;

  /**
   * The length to which to truncate the title.
   *
   * @var false|int
   */
  private mixed $maxLength;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->validate = $this->configuration['validate'] ?? $this->validate;
    $this->maxLength = $this->configuration['max_length'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($node, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $xpath = $row->get($this->configuration['xpath']);

    $parts = static::getTitleParts($xpath, $node);

    $paragraph = Paragraph::create([
      'type' => 'title',
      'field_title' => $this->getTitle($parts, $migrate_executable),
      'field_title_type' => $parts['@type'],
    ]);

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
        throw new MigrateSkipRowException(strtr('Paragraph (:type) validation error(s): :errors', [
          ':type' => 'title',
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

  // Map of keys in which to store to relative xpaths to store.
  const MAP = [
    '@type' => '@type',
    'nonSort' => 'mods:nonSort[1]',
    'subTitle' => 'mods:subTitle[1]',
    'partNumber' => 'mods:partNumber[1]',
    'partName' => 'mods:partName[1]',
    'title' => 'mods:title[1]',
  ];

  /**
   * Gets the parts of the title we need, as an array.
   *
   * @param \DOMXPath $xpath
   *   The XPath instance in which to query.
   * @param \DOMNode $node
   *   The node relative which to query.
   *
   * @return array
   *   An array of parts we want from the node, keyed by their node name, with
   *   a single string as the value of each, or false-y if no value was parsed.
   *   The title type is keyed as '@type'.
   */
  protected static function getTitleParts(\DOMXPath $xpath, \DOMNode $node) {
    $parts = [];

    foreach (static::MAP as $key => $query) {
      $parts[$key] = $xpath->evaluate("string($query)", $node);
    }

    return $parts;
  }

  /**
   * Gets a string to represent the title field.
   *
   * @param string[] $title_parts
   *   The parts to assemble into the title.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration executable, so we can log messages if necessary.
   *
   * @return string
   *   The title string.
   */
  protected function getTitle(array $title_parts, MigrateExecutableInterface $migrate_executable) {
    $title = '';
    if (!empty($title_parts['nonSort'])) {
      $title .= "{$title_parts['nonSort']} ";
    }
    $title .= $title_parts['title'];
    if (!empty($title_parts['subTitle'])) {
      $title .= ": {$title_parts['subTitle']}";
    }
    if (!empty($title_parts['partNumber'])) {
      $title .= ". {$title_parts['partNumber']}";
    }
    if (!empty($title_parts['partName'])) {
      $title .= ". {$title_parts['partName']}";
    }

    if ($this->maxLength < 1 || mb_strlen($title) <= $this->maxLength) {
      return $title;
    }
    else {
      $truncated = Unicode::truncate($title, $this->maxLength, TRUE, TRUE);
      $migrate_executable->saveMessage(
        strtr('Truncated ":title" to ":truncated" to fit within configured limit of :limit characters.', [
          ':title' => $title,
          ':truncated' => $truncated,
          ':limit' => $this->maxLength,
        ]),
        MigrationInterface::MESSAGE_WARNING
      );
      return $truncated;
    }
  }

}
