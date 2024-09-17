<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * General FOXML file handling plugin.
 *
 * Accepts:
 * - method: One of "copy" (to copy the file) or "direct" (to directly use the
 *   file). Defaults to "copy". Can be set with the DGI_MIGRATE_FILE_METHOD
 *   environment variable.
 * - pid: Property in the row containing the PID, to build out a destination if
 *   copying.
 * - destination: Property in the row containing the destination, to build out
 *   a destination path if copying.
 * - date: Property in the row containing a date with which to build a path in
 *   the destination, when copying.
 * - mimetype; Property in the row containing the MIME type of the file, to
 *   determine an extension.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.foxml_file"
 * )
 */
class FoxmlFile extends ProcessPluginBase {

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MigrationInterface $migration,
    protected TransliterationInterface $transliteration,
    protected MigrateProcessInterface $naiveCopyPlugin,
    protected MigrateProcessInterface $extensionPlugin,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $configuration['method'] ??= getenv('DGI_MIGRATE_FILE_METHOD') ?: 'copy';
    assert(in_array($configuration['method'], ['copy', 'direct']));
    /** @var \Drupal\migrate\Plugin\MigratePluginManagerInterface $process_plugin_manager */
    $process_plugin_manager = $container->get('plugin.manager.migrate.process');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('transliteration'),
      $process_plugin_manager->createInstance('dgi_migrate.naive_file_copy'),
      $process_plugin_manager->createInstance('dgi_migrate.process.extension_from_mimetype'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return match ($this->configuration['method']) {
      'copy' => $this->naiveCopyPlugin->transform([
        $value,
        $this->getDestinationPath($migrate_executable, $row, $destination_property),
      ], $migrate_executable, $row, $destination_property),
      'direct' => $value,
    };
  }

  /**
   * Build out path.
   */
  protected function getDestinationPath(MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) : string {
    $pid = $row->get($this->configuration['pid']);
    // Adapted from https://api.drupal.org/api/drupal/core%21modules%21migrate%21src%21Plugin%21migrate%21process%21MachineName.php/class/MachineName/10
    $safe_pid = preg_replace(
      '/_+/',
      '_',
      preg_replace(
        '/[^a-z0-9_]+/',
        '_',
        strtolower(
          $this->transliteration->transliterate($pid, LanguageInterface::LANGCODE_DEFAULT, '_'),
        ),
      )
    );

    $dest_dir = $row->get($this->configuration['destination']);
    $path = date('Y-m', $row->get($this->configuration['date']));
    $ext = $this->extensionPlugin->transform($row->get($this->configuration['mimetype']), $migrate_executable, $row, $destination_property);

    return "{$dest_dir}/{$path}/{$safe_pid}.{$ext}";
  }

}
