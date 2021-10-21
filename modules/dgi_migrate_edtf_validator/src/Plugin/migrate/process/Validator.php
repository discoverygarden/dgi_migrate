<?php

namespace Drupal\dgi_migrate_edtf_validator\Plugin\migrate\process;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\controlled_access_terms\EDTFUtils;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Skips processing the current row when the EDTF date isn't valid.
 *
 * Available configuration keys:
 * - intervals (optional): Boolean of whether this field is supporting intervals
 *   or not, defaults to TRUE.
 * - sets (optional): Boolean of whether this field is supporting sets or not,
 *   defaults to TRUE.
 * - strict (optional): Boolean of whether this field is supporting calendar
 *   dates or not, defaults to FALSE.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate_edtf_validator"
 * )
 */
class Validator extends ProcessPluginBase implements ConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $errors = EDTFUtils::validate($value, $this->configuration['intervals'], $this->configuration['sets'], $this->configuration['strict']);
    if (!empty($errors)) {
      throw new MigrateSkipRowException(strtr('The value: ":value" for ":property" is not a valid EDTF date: :errors', [
        ':value' => $value,
        ':property' => $destination_property,
        ':errors' => implode(' ', $errors),
      ]));
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    foreach (['intervals', 'sets', 'strict'] as $key) {
      $this->configuration[$key] = (!isset($configuration[$key]) ? $configuration[$key] : $this->defaultConfiguration()[$key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'intervals' => TRUE,
      'sets' => TRUE,
      'strict' => FALSE,
    ];
  }

}
