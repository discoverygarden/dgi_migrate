<?php

namespace Drupal\dgi_migrate\Plugin\dgi_migrate\locker;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\dgi_migrate\Attribute\Locker;
use Drupal\dgi_migrate\Plugin\migrate\process\LockingMigrationLookup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base implementation using flock.
 */
#[Locker('flock')]
class Flock extends PluginBase implements LockerInterface, ContainerFactoryPluginInterface {

  /**
   * An array of SplFileObjects, to facilitate locking.
   *
   * @var \SplFileObject[]
   */
  protected array $lockFiles = [];

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FileSystemInterface $fileSystem,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function acquireLock(string $name, int $mode = LOCK_EX, bool &$would_block = FALSE): bool {
    return $this->getLockFile($name)->flock($mode, $would_block);
  }

  /**
   * {@inheritDoc}
   */
  public function releaseLock(string $name): bool {
    return $this->getLockFile($name)->flock(LOCK_UN);
  }

  /**
   * Get an \SplFileObject instance to act as the lock.
   *
   * @param string $name
   *   The name of the lock to acquire. Should result in a file being created
   *   under the temporary:// scheme of the same name, against which `flock`
   *   commands will be issued.
   *
   * @return \SplFileObject
   *   The \SplFileObject instance against which to lock.
   */
  protected function getLockFile(string $name) : \SplFileObject {
    if (!isset($this->lockFiles[$name])) {
      $file_name = "temporary://{$name}";
      $directory = $this->fileSystem->dirname($file_name);
      $basename = $this->fileSystem->basename($file_name);
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
      $file_name = "{$directory}/{$basename}";

      touch($file_name);
      $this->lockFiles[$name] = $file = new \SplFileObject($file_name, 'a+');
    }

    return $this->lockFiles[$name];
  }

  /**
   * {@inheritDoc}
   */
  public function acquireControl(): bool {
    return $this->acquireLock(LockingMigrationLookup::CONTROL_LOCK);
  }

  /**
   * {@inheritDoc}
   */
  public function releaseControl(): bool {
    return $this->releaseLock(LockingMigrationLookup::CONTROL_LOCK);
  }

}
