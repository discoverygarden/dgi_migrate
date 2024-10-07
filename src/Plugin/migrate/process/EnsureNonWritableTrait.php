<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Helper trait for migrations where source files are directly referenced.
 *
 * More specifically, where source files are referenced by file entities, as
 * rolling back a migration will attempt to delete the files of those entities
 * that are rolled back. Rolling back a migration only to have all the files go
 * AWOL would be quite bad.
 */
trait EnsureNonWritableTrait {

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
    if (!is_file($path)) {
      throw new MigrateSkipRowException(strtr('Source ({path}) does not appear to be a plain file; skipping row.', [
        '{path}' => $path,
      ]));
    }
    if (is_dir($path)) {
      throw new MigrateSkipRowException(strtr('Source ({path}) appears to be a directory; skipping row.', [
        '{path}' => $path,
      ]));
    }
    if (!is_readable($path)) {
      throw new MigrateSkipRowException(strtr('Source ({path}) does not appear to be readable; skipping row.', [
        '{path}' => $path,
      ]));
    }
    if (is_writable($path)) {
      throw new MigrateSkipRowException(strtr('Source ({path}) appears to be writable(/deletable); skipping row.', [
        '{path}' => $path,
      ]));
    }
    if (is_writable($this->getFileSystem()->dirname($path))) {
      throw new MigrateSkipRowException(strtr('Directory of source ({path}) appears writable(/deletable); skipping row.', [
        '{path}' => $path,
      ]));
    }

    return $path;
  }

  /**
   * Drupal's file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Accessor for Drupal's file system service.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   Drupal's file system service.
   */
  protected function getFileSystem() : FileSystemInterface {
    if (!isset($this->fileSystem)) {
      $this->fileSystem = \Drupal::service('file_system');
    }

    return $this->fileSystem;
  }

}
