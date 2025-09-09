<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Naive file_copy implementation.
 *
 * The core "file_copy" is rather opinionated, complicating the use of the
 * php:// scheme.
 *
 * Extends "file_copy", additionally accepting:
 * - force_stub: Boolean to force the copying when the row being processed
 *   appears to be a stub.
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
   * Boolean if we should force copying when the row is a stub.
   *
   * @var bool
   */
  protected bool $forceStub;

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    StreamWrapperManagerInterface $stream_wrappers,
    FileSystemInterface $file_system,
    ConfigFactoryInterface $config_factory,
    protected LoggerInterface $logger,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $stream_wrappers,
      $file_system,
      new class([], '', []) extends ProcessPluginBase {

        /**
         * {@inheritDoc}
         */
        public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) : void {
          throw new \LogicException('Not implemented!');
        }

      },
    );

    $this->migrateConfig = $config_factory->get('dgi_migrate.settings');
    $this->forceStub = $this->configuration['force_stub'] ?? FALSE;
    if ($this->configuration['move']) {
      throw new \LogicException("Moving files is not supported with {$this->getPluginId()}.");
    }
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
      $container->get('config.factory'),
      $container->get('logger.factory')->get('dgi_migrate.naive_file_copy'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a URI of NULL so it will get
    // stubbed by the general process.
    if (!$this->forceStub && $row->isStub()) {
      return NULL;
    }
    [$source, $destination] = $value;

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

    return $this->writeFile($source, $destination, $this->configuration['file_exists']);
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
    if (($actual_destination = $this->fileSystem->getDestinationFilename($destination, $replace)) === FALSE) {
      return $destination;
    }
    try {
      // XXX: PHP description of how file_exists() responds to things making use
      // of "php://filter", that it returns as per the wrapped stream does not
      // appear to be correct... the usual $this->fileSystem->move/copy calls
      // would call file_exists() on the source, so let's avoid it.
      $spool_name = @tempnam($this->migrateConfig->get('spool_dir') ?: $this->fileSystem->getTempDirectory(), 'b64spool');
      try {
        $spool_fp = fopen($spool_name, 'r+b');
        if (!$spool_fp) {
          throw new FileException("Failed to open spool.");
        }

        if (str_starts_with($source, 'php://filter')) {
          $target = '/resource=';
          $pos = strpos($source, $target);
          $actual_source = substr($source, $pos + strlen($target));
          $source_fp = fopen($actual_source, 'rb');
          if (!$source_fp) {
            throw new FileException("Failed to open source: {$source}");
          }
          if (!($source_stat = fstat($source_fp))) {
            $this->logger->warning('Failed to stat source file ({source}, {actual_source}); continuing without comparing sizes.', [
              'source' => $source,
              'actual_source' => $actual_source,
            ]);
          }

          $pipes = [];
          $proc = proc_open($this->getBase64Command(), [
            0 => ['pipe', 'r'],
            1 => $spool_fp,
          ], $pipes);
          $written_to_filter = stream_copy_to_stream($source_fp, $pipes[0]);

          if ($source_stat && $source_stat['size'] !== $written_to_filter) {
            throw new FileException("Failed to write all bytes to filter: {$written_to_filter}/{$source_stat['size']} of {$source}");
          }
          if (!fflush($pipes[0])) {
            throw new FileException("Failed to flush pipe for {$source}");
          }
          if (!fclose($pipes[0])) {
            throw new FileException("Failed to close pipe for {$source}");
          }
          if (($exit_code = proc_close($proc)) !== 0) {
            throw new FileException("Unexpected exit code for {$source}, got {$exit_code}");
          }
          if (!fclose($source_fp)) {
            throw new FileException("Failed to close source file pointer, for {$source}");
          }
        }
        else {
          $source_fp = @fopen($source, 'rb');
          if (!$source_fp) {
            throw new FileException("Failed to open source.");
          }
          if (!($source_stat = fstat($source_fp))) {
            $this->logger->warning('Failed to stat source file ({source}, {actual_source}); continuing without comparing sizes.', [
              'source' => $source,
            ]);
          }
          $written_to_spool = stream_copy_to_stream($source_fp, $spool_fp);
          if ($source_stat && $source_stat['size'] !== $written_to_spool) {
            throw new FileException("Failed to write all bytes to spool: {$written_to_spool}/{$source_stat['size']} of {$source}");
          }
        }
        if (!fflush($spool_fp)) {
          throw new FileException("Failed to flush spool.");
        }

        if (fseek($spool_fp, 0, SEEK_SET) !== 0) {
          throw new FileException("Failed to rewind spool.");
        }
        clearstatcache(filename: $spool_name);
        $spool_stat = fstat($spool_fp);
        $destination_fp = fopen($actual_destination, 'w+b');
        if (!$destination_fp) {
          throw new FileException("Failed to open destination file {$destination} (actual destination: {$actual_destination}).");
        }
        $written_to_destination = stream_copy_to_stream($spool_fp, $destination_fp);
        if ($spool_stat['size'] !== $written_to_destination) {
          throw new FileException("Failed to write all bytes to destination: {$written_to_destination}/{$spool_stat['size']} , {$destination} (actual destination: {$actual_destination})");
        }
        if (!fflush($destination_fp)) {
          throw new FileException("Failed to flush {$source} to {$destination} (actual destination: {$actual_destination})");
        }
        if (!fclose($destination_fp)) {
          throw new FileException("Failed to close destination {$destination} (actual destination: {$actual_destination})");
        }
        if (!fclose($source_fp)) {
          throw new FileException("Failed to close source {$source}");
        }
        return $actual_destination;
      }
      finally {
        if (isset($proc)) {
          $status = @proc_get_status($proc);
          if ($status['running']) {
            @proc_terminate($proc, 9);
            @proc_close($proc);
          }
          unset($proc);
        }

        @unlink($spool_name);
      }
    }
    catch (FileException $e) {
      throw new MigrateException("File $source could not be copied to $destination", previous: $e);
    }
  }

}
