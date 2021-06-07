<?php

namespace Drupal\dgi_migrate_imagemagick_cleanup\EventSubscriber;

use Drupal\dgi_migrate_imagemagick_cleanup\Event\TempImageEvent;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\imagemagick\Event\ImagemagickExecutionEvent;
use Drupal\imagemagick\ImagemagickExecArguments;

use Drupal\Core\DestructableInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Cleaning event subscriber.
 */
class EventSubscriber implements EventSubscriberInterface, DestructableInterface {

  /**
   * Stack of paths per row.
   *
   * @var array
   */
  protected $stack;

  /**
   * Logging channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Filesystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructor.
   */
  public function __construct(
    LoggerInterface $logger,
    FileSystemInterface $file_system,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    EventDispatcherInterface $event_dispatch
  ) {
    $this->logger = $logger;
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->eventDispatch = $event_dispatch;
    $this->stack = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_IMPORT => 'push',
      TempImageEvent::EVENT_NAME => 'newTemp',
      ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH => [
        'ensureSourceLocalPath',
        100,
      ],
      MigrateEvents::POST_IMPORT => 'pop',
    ];
  }

  /**
   * Event callback; push another entry onto the stack.
   */
  public function push(MigratePreRowSaveEvent $event) {
    $this->stack[] = [];
  }

  /**
   * Event callback; no-op outside the context of a migration.
   *
   * @see \Drupal\imagemagick\EventSubscriber\ImagemagickEventSubscriber::ensureSourceLocalPath()
   */
  public function ensureSourceLocalPath(ImagemagickExecutionEvent $event) {
    $current = end($this->stack);
    if (!$current) {
      // Do nothing; let the imagemagick module handle it.
      return;
    }

    // Do our thing.
    $arguments = $event->getExecArguments();
    $this->doEnsureSourceLocalPath($arguments);
  }

  /**
   * Event callback; add in the path to the current/last stack entry.
   */
  public function newTemp(TempImageEvent $event) {
    $current = array_pop($this->stack);
    if (!$current) {
      return;
    }

    $current[] = $event->getPath();

    $this->stack[] = $current;
  }

  /**
   * Event callback; pop the stack, cleaning up any contained entries.
   */
  public function pop(MigratePostRowSaveEvent $event) {
    $to_drop = array_pop($this->stack);

    if (!$to_drop) {
      return;
    }

    $this->deleteChunk($to_drop);
  }

  /**
   * Helper; delete all entries in a given chunk.
   *
   * @param array $chunk
   *   An array of paths/URIs to delete.
   */
  protected function deleteChunk(array $chunk) {
    array_map([$this->fileSystem, 'delete'], $chunk);
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    array_map([$this, 'deleteChunk'], $this->stack);
    // XXX: Reset to an empty array, just in case.
    $this->stack = [];
  }

  /**
   * Ensure local source path.
   *
   * Essentially copypasta from imagemagick, with the different of emitting an
   * event instead of registering the file for deletion on shutdown.
   *
   * @see \Drupal\imagemagick\EventSubscriber\ImagemagickEventSubscriber::doEnsureSourceLocalPath()
   */
  protected function doEnsureSourceLocalPath(ImagemagickExecArguments $arguments) {
    // Early return if already set.
    if (!empty($arguments->getSourceLocalPath())) {
      return;
    }

    $source = $arguments->getSource();
    if (!$this->streamWrapperManager->isValidUri($source)) {
      // The value of $source is likely a file path already.
      $arguments->setSourceLocalPath($source);
    }
    else {
      // If we can resolve the realpath of the file, then the file is local
      // and we can assign the actual file path.
      $path = $this->fileSystem->realpath($source);
      if ($path) {
        $arguments->setSourceLocalPath($path);
      }
      else {
        // We are working with a remote file, copy the remote source file to a
        // temp one and set the local path to it.
        try {
          $temp_path = $this->fileSystem->tempnam('temporary://', 'imagemagick_');
          $this->fileSystem->unlink($temp_path);
          $temp_path .= '.' . pathinfo($source, PATHINFO_EXTENSION);
          $path = $this->fileSystem->copy($arguments->getSource(), $temp_path, FileSystemInterface::EXISTS_ERROR);
          $arguments->setSourceLocalPath($this->fileSystem->realpath($path));
          $this->eventDispatcher->dispatch(TempImageEvent::EVENT_NAME, new TempImageEvent($arguments->getSourceLocalPath()));
        }
        catch (FileException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }

}
