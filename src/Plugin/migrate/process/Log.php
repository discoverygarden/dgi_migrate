<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Allow for more complete messages.
 *
 * Otherwise, messages can only push through bare values, which is less-than
 * readable... now we can provide some context around the value.
 *
 * Example:
 * @code
 * process:
 *   plugin: dgi_migrate.process.log
 *   source: fid
 *   template: 'The file ID is :value'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.log"
 * )
 */
class Log extends ProcessPluginBase {

  /**
   * Log message template, into which we will substitute the value as ":value".
   *
   * @var string
   */
  protected $template;

  /**
   * The level at which to log.
   *
   * Should be one of the \Drupal\migrate\Plugin\MigrationInterface::MESSAGE_*
   * constants.
   *
   * @var int
   */
  protected $level;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->template = $this->configuration['template'];
    $this->level = $this->configuration['level'];
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migrate_executable->saveMessage(
      strtr($this->template, [
        ':value' => $value,
      ]),
      $this->level
    );

    return $value;
  }

}
