<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
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
 * - destination: Property in the row containing the destination, to build out
 *   a destination path if copying.
 * - date: Property in the row containing a date with which to build a path in
 *   the destination, when copying.
 * - filename: Property in the row containing a filename.
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
    protected MigrateProcessInterface $naiveCopyPlugin,
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
      $process_plugin_manager->createInstance('dgi_migrate.naive_file_copy', [], $migration),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return match ($this->configuration['method']) {
      'copy' => $this->naiveCopyPlugin->transform([
        $value,
        $this->getDestinationPath($row),
      ], $migrate_executable, $row, $destination_property),
      'direct' => $this->ensureNonWritable($value),
    };
  }

  /**
   * Build out path.
   */
  protected function getDestinationPath(Row $row) : string {
    $dest_dir = $row->get($this->configuration['destination']);
    $path = date('Y-m', $row->get($this->configuration['date']));
    $filename = $row->get($this->configuration['filename']);

    return "{$dest_dir}/{$path}/{$filename}";
  }

  /**
   * Helper; ensure the given path does not appear to be writable.
   *
   * @param string $path
   *   The path to check.
   *
   * @return string
   *   The path unchanged if non-writable; otherwise, we throw the skip
   *   exception.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   If the file appears to be writable/deletable.
   */
  protected function ensureNonWritable(string $path) : string {
    $file = new \SplFileInfo($path);

    if ($file->isFile() && !$file->isDir() && $file->isReadable() && !$file->isWritable() &&
      !$file->getPathInfo()->isWritable()) {
      throw new MigrateSkipRowException('Source appears writable (or not readable); skipping row.');
    }

    return $path;
  }

}
