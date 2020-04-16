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
        $source_fp = fopen($source, 'rb');
        if (!$source_fp) {
          throw new FileException("Failed to open source.");
        }
        $spool_fp = fopen('php://temp', 'r+b');
        while (!feof($source_fp)) {
          fwrite($spool_fp, fread($source_fp, 2**20));
        }
        fclose($source_fp);
        fseek($spool_fp, 0);
        $dest_fp = fopen($actual_destination, 'wb');
        if (!$dest_fp) {
          fclose($spool_fp);
          throw new FileException("Failed to open destination.");
        }
        $result = stream_copy_to_stream($spool_fp, $dest_fp);
        fclose($spool_fp);
        fclose($dest_fp);
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
