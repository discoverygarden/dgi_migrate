<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\Unicode;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Truncates a string to a specified length.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.truncate",
 *   handle_multiples = TRUE
 * )
 *
 * Available configuration keys:
 * - max_length: (required) The maximum number of characters to keep.
 * - word_safe: (optional) Whether to truncate at word boundaries. Defaults to FALSE.
 * - add_ellipsis: (optional) Whether to add ellipsis (...) to truncated strings. Defaults to FALSE.
 *
 * Examples:
 *
 * @code
 * process:
 *   field_title:
 *     plugin: dgi_migrate.truncate
 *     source: title
 *     max_length: 255
 *     word_safe: true
 *     add_ellipsis: true
 * @endcode
 */
class Truncate extends ProcessPluginBase {

  /**
   * Character count to truncate to.
   *
   * @var int
   */
  protected int $maxLength;

  /**
   * If the truncate should be word safe or not.
   *
   * @var bool
   */
  protected bool $wordSafe;

  /**
   * If the truncate should end with an ellipsis character.
   *
   * @var bool
   */
  protected bool $addEllipsis;

  /**
   * Constructor.
   * @throws MigrateException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->maxLength = $this->configuration['max_length'];

    if (!isset($this->maxLength)) {
      throw new MigrateException('dgi_migrate.truncate is missing "max_length" configuration.');
    }
    if ($this->maxLength <= 0) {
      throw new MigrateException('dgi_migrate.truncate "max_length" configuration must be a positive integer.');
    }

    $this->wordSafe = $this->configuration['word_safe'] ?? FALSE;
    $this->addEllipsis = $this->configuration['add_ellipsis'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value) || !is_string($value)) {
      return $value;
    }

    return Unicode::truncate($value, $this->maxLength, $this->wordSafe, $this->addEllipsis);
  }

}
