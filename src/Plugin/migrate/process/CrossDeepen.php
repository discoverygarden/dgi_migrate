<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Crosses and deepens two non-associative arrays for multi-value mapping.
 *
 * Available configuration keys:
 * - source: The source arrays.
 * - first: The key used to map the first array.
 * - second: The key used to map the second array.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.cross_deepen",
 *   handle_multiples = TRUE
 * )
 */
class CrossDeepen extends ProcessPluginBase {

  /**
   * Key to map to the first array.
   *
   * @var string
   */
  protected $first;

  /**
   * Key to map to the second array.
   *
   * @var string
   */
  protected $second;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->first = $this->configuration['first'] ?? 'keyOne';
    $this->second = $this->configuration['second'] ?? 'keyTwo';
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('Input should be an array.');
    }

    if (count($value) != 2) {
      throw new MigrateException('Input requires exactly two items.');
    }

    $first_array = is_array($value[0]) ? $value[0] : [$value[0]];
    $second_array = is_array($value[1]) ? $value[1] : [$value[1]];

    if (count($first_array) != count($second_array)) {
      throw new MigrateException('Both arrays must be of the same length.');
    }

    $modified_value = [];
    for ($index = 0; $index < count($first_array); ++$index) {
      $modified_value[] = [
        $this->first => $first_array[$index],
        $this->second => $second_array[$index],
      ];
    }
    return $modified_value;
  }

}
