<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\Mime\MimeTypes;

/**
 * Determine a file extension from a MIME-type.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.extension_from_mimetype"
 * )
 */
class DetermineExtension extends ProcessPluginBase {

  /**
   * MIME-type utility.
   *
   * @var \Symfony\Component\Mime\MimeTypes
   */
  protected $mimeTypes;

  /**
   * The migration being executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface|null
   */
  protected ?MigrationInterface $migration;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->mimeTypes = new MimeTypes();
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateException('Value is expected to be a string.');
    }

    $result = $this->mimeTypes->getExtensions($value);
    if ($result) {
      // Determined an appropriate extension.
      return reset($result);
    }

    if ($this->migration) {
      $this->migration->getIdMap()->saveMessage(
        $row->getSourceIdValues(),
        "Falling back to the second part of the MIME-type for an extension.",
        MigrationInterface::MESSAGE_NOTICE
      );
    }
    [, $ext] = explode('/', $value, 2);
    return $ext;
  }

}
