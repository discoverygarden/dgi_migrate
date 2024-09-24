<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

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
  protected static function ensureNonWritable(string $path) : string {
    $file = new \SplFileInfo($path);

    if (!$file->isFile()) {
      throw new MigrateSkipRowException(strtr('Source ({path}) does not appear to be a plain file; skipping row.', [
        '{path}' => $path,
      ]));
    }
    if ($file->isDir()) {
      throw new MigrateSkipRowException(strtr('Source ({path}) appears to be a directory; skipping row.', [
        '{path}' => $path,
      ]));
    }
    if (!$file->isReadable()) {
      throw new MigrateSkipRowException(strtr('Source ({path}) does not appear to be readable; skipping row.', [
        '{path}' => $path,
      ]));
    }
    if ($file->isWritable()) {
      throw new MigrateSkipRowException(strtr('Source ({path}) appears to be writable(/deletable); skipping row.', [
        '{path}' => $path,
      ]));
    }
    if ($file->getPathInfo()?->isWritable()) {
      throw new MigrateSkipRowException(strtr('Directory of source ({path}) appears writable(/deletable); skipping row.', [
        '{path}' => $path,
      ]));
    }

    return $path;
  }

}
