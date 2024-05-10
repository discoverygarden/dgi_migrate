<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class Realpath extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal's file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $real_path = $this->fileSystem->realpath($value);
    if (!$real_path) {
      throw new MigrateException("Cannot get the real path for uri {$value}");
    }
    return $real_path;
  }

}
