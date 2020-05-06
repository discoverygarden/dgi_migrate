<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Symfony\Component\Mime\MimeTypes;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;

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
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->mimeTypes = new MimeTypes();
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

    $migrate_executable->saveMessage(
      "Falling back to the second part of the MIME-type for an extension.",
      MigrationInterface::MESSAGE_NOTICE
    );
    list(, $ext) = explode('/', $value, 2);
    return $ext;
  }

}
