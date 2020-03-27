<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\dgi_migrate\Utility\Fedora3\FoxmlParser;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateException;

/**
 * Call an accessor from an object.
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

    return call_user_func([$value, $method]);
  }

}
