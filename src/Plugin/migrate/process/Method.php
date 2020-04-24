<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Call a method on an object.
 *
 * Example:
 * @code
 * process:
 *   - plugin: dgi_migrate.load_entity
 *     source: '@some_object'
 *     method: someMethod
 *     args:
 *       - alpha
 *       - bravo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.method"
 * )
 */
class Method extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_object($value)) {
      throw new MigrateException('Input should be an object.');
    }
    $method = $this->configuration['method'];

    if (isset($this->configuration['args'])) {
      if (!is_array($this->configuration['args'])) {
        throw new MigrateException('The arguments for the method should be in an array.');
      }
      return call_user_func_array([$value, $method], $this->configuration['args']);
    }
    else {
      return call_user_func([$value, $method]);
    }

  }

}
