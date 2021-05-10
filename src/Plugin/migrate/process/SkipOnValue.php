<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate_plus\Plugin\migrate\process\SkipOnValue as Upstream;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Logging skip on value plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.skip_on_value"
 * )
 */
class SkipOnValue extends Upstream {

  /**
   * {@inheritdoc}
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      return parent::row($value, $migrate_executable, $row, $destination_property);
    }
    catch (MigrateSkipRowException $e) {
      $migrate_executable->saveMessage(strtr($this->configuration['message'] ?? 'Skipping row with ":value" when processing :property.', [
        ':value' => $this->configuration['value'],
        ':property' => $destination_property,
      ]), MigrationInterface::MESSAGE_WARNING);
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      return parent::process($value, $migrate_executable, $row, $destination_property);
    }
    catch (MigrateSkipProcessException $e) {
      $migrate_executable->saveMessage(strtr($this->configuration['message'] ?? 'Skipping processing with ":value" when processing :property.', [
        ':value' => $this->configuration['value'],
        ':property' => $destination_property,
      ]), MigrationInterface::MESSAGE_WARNING);
      throw $e;
    }
  }

}
