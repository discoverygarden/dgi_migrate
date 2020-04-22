<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;

/**
 * Naive file_copy implementation.
 *
 * The core "file_copy" is rather opinionated, complicating the use of the
 * php:// scheme.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.naive_file_copy"
 * )
 */
class NaiveFileCopy extends FileCopy implements ContainerFactoryPluginInterface {

  /**
   * DGI Migrate's configuration object.
   *
   * @var \Drupal\Core\Config\ConfigBase
   */
  protected $migrateConfig;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StreamWrapperManagerInterface $stream_wrappers, FileSystemInterface $file_system, MigrateProcessInterface $download_plugin, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $stream_wrappers, $file_system, $download_plugin);

    $this->migrateConfig = $config_factory->get('dgi_migrate.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('stream_wrapper_manager'),
      $container->get('file_system'),
      $container->get('plugin.manager.migrate.process')->createInstance('download', $configuration),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a URI of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    list($source, $destination) = $value;

    // Check if a writable directory exists, and if not try to create it.
    $dir = $this->getDirectory($destination);
    // If the directory exists and is writable, avoid
    // \Drupal\Core\File\FileSystemInterface::prepareDirectory() call and write
    // the file to destination.
    if (!is_dir($dir) || !is_writable($dir)) {
      if (!$this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        throw new MigrateException("Could not create or write to directory '$dir'");
      }
    }

    $final_destination = $this->writeFile($source, $destination, $this->configuration['file_exists']);
    if ($final_destination) {
      return $final_destination;
    }
    throw new MigrateException("File $source could not be copied to $destination");
  }

  /**
   * Helper; build out the command to decode base64, streamwise.
   *
   * @return string
   *   The command.
   */
  protected function getBase64Command() : string {
    return implode(' ', array_map('escapeshellarg', [
      $this->migrateConfig->get('openssl_executable'),
      'base64',
      '-d',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function writeFile($source, $destination, $replace = FileSystemInterface::EXISTS_REPLACE) {
    // Check if there is a destination available for copying. If there isn't,
    // it already exists at the destination and the replace flag tells us to not
    // replace it. In that case, return the original destination.
    if ($this->fileSystem->getDestinationFilename($destination, $replace) === FALSE) {
      return $destination;
    }
    try {
      // XXX: PHP description of how file_exists() responds to things making use
      // of "php://filter", that it returns as per the wrapped stream does not
      // appear to be correct... the usual $this->fileSystem->move/copy calls
      // would call file_exists() on the source, so let's avoid it.
      $actual_destination = $this->fileSystem->saveData('', $destination, $replace);

      if ($this->configuration['move']) {
        $result = rename($source, $actual_destination);
      }
      else {
        $spool_name = tempnam($this->migrateConfig->get('temp_dir'), 'b64spool');
        try {
          $spool_fp = fopen($spool_name, 'r+b');

          if (strpos($source, 'php://filter') === 0) {
            $target = '/resource=';
            $pos = strpos($source, $target);
            $actual_source = substr($source, $pos + strlen($target));
            $source_fp = fopen($actual_source, 'rb');
            $pipes = [];
            $proc = proc_open($this->getBase64Command(), [
              0 => ['pipe', 'r'],
              1 => $spool_fp,
            ], $pipes);
            stream_copy_to_stream($source_fp, $pipes[0]);
            #while (!feof($source_fp)) {
            #  fwrite($pipes[0], fread($source_fp, 2**20));
            #}
            fclose($pipes[0]);
            proc_close($proc);
            fclose($source_fp);
          }
          else {
            $source_fp = fopen($source, 'rb');
            if (!$source_fp) {
              throw new FileException("Failed to open source.");
            }
            stream_copy_to_stream($source_fp, $spool_fp);
          }
          fflush($spool_fp);

          $result = copy($spool_name, $actual_destination);
        }
        finally {
          if (isset($spool_fp)) {
            fclose($spool_fp);
          }
          unlink($spool_name);
        }
      }
      if ($result === FALSE) {
        throw new FileException("Failed to move {$source} to {$destination}.");
      }
      else {
        return $actual_destination;
      }
    }
    catch (FileException $e) {
      return FALSE;
    }
  }

}
