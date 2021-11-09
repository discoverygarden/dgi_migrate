<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Wrapper for FileSystem::realpath.
 *
 * Example:
 * @code
 * process:
 *   - plugin: dgi_migrate.realpath
 *     uri: '@some_uri'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.realpath"
 * )
 */
class Realpath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    \Drush\Drush::output()->writeln("testing {$value}");
    $uri = $this->configuration['uri'] ?? $value;
    $real_path = \Drupal::service('file_system')->realpath($uri);
    if (!$real_path) {
      throw new MigrateException("Cannot get the real path for uri {$uri}");
    }
    return $real_path;
  }

}
