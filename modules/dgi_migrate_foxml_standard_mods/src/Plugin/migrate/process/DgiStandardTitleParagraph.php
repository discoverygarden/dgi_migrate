<?php

namespace Drupal\dgi_migrate_foxml_standard_mods\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

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
   * The titleInfo node we're working with.
   *
   * @var \DOMNode
   */
  protected $node;

  /**
   * Parsed bits of the title.
   *
   * Don't access this directly; rather, use $this->getTitleParts().
   *
   * @var array
   */
  protected $titleParts;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    assert($value instanceof \DOMNode);
    $this->node = $value;

    $paragraph = Paragraph::create(
      [
        'type' => 'title',
        'field_title' => $this->getTitle(),
        'field_title_type' => $this->getTitleType(),
      ]
    );

    $validate = $this->configuration['validate'] ?? FALSE;

    $paragraph->setValidationRequired($validate);

    if ($validate) {
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

  /**
   * Gets the parts of the title we need, as an array.
   *
   * @return array
   *   An array of parts we want from the node, keyed by their node name, with
   *   a single string as the value of each, or NULL if no value was parsed. The
   *   title type is keyed as '@type'.
   */
  protected function getTitleParts() {
    if (empty($this->titleParts)) {
      $this->titleParts = [
        '@type' => NULL,
        'nonSort' => NULL,
        'subTitle' => NULL,
        'partNumber' => NULL,
        'partName' => NULL,
        'title' => NULL,
      ];

      foreach ($this->node->childNodes as $child) {
        if (isset($this->titleParts[$child->localName])) {
          $this->titleParts[$child->localName] = $child->textContent;
          if ($child->localName = 'title') {
            $this->titleParts['@type'] = $child->getAttribute('type');
          }
        }
      }
    }

    return $this->titleParts;
  }

  /**
   * Gets a string to represent the title field.
   *
   * @return string
   *   The title string.
   */
  protected function getTitle() {
    $title_parts = $this->getTitleParts();

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

    return $title;
  }

  /**
   * Gets a string to represent the title type.
   *
   * @return string
   *   The title type.
   */
  protected function getTitleType() {
    return $this->getTitleParts()['@type'];
  }

}
