<?php

namespace Drupal\dgi_migrate_imagemagick_cleanup\EventSubscriber;

use Drupal\Core\DestructableInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\dgi_migrate\EventSubscriber\StubMigrateEvents;
use Drupal\dgi_migrate_imagemagick_cleanup\Event\TempImageEvent;
use Drupal\imagemagick\Event\ImagemagickExecutionEvent;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
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
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Boolean flag, enabling the emitting of debug messages.
   *
   * XXX: Not presently exposed for configuration anywhere... kinda expected to
   * flip it by hand, if dealing with these behaviours.
   *
   * @var bool
   */
  protected $emitDebug = FALSE;

  /**
   * Constructor.
   */
  public function __construct(
    LoggerInterface $logger,
    FileSystemInterface $file_system,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    EventDispatcherInterface $event_dispatcher,
  ) {
    $this->logger = $logger;
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->stack = [];
  }

  /**
   * Helper; emit debug messages if so-configured.
   */
  protected function debug($message, $context = []) {
    if ($this->emitDebug) {
      $formatter = function ($item) {
        return print_r($item, TRUE);
      };
      $this->logger->debug($message, array_map($formatter, $context));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_ROW_SAVE => 'push',
      TempImageEvent::EVENT_NAME => 'newTemp',
      ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH => [
        'ensureSourceLocalPath',
        100,
      ],
      MigrateEvents::POST_ROW_SAVE => 'pop',
      StubMigrateEvents::PRE_SAVE => 'push',
      StubMigrateEvents::POST_SAVE => 'pop',
    ];
  }

  /**
   * Event callback; push another entry onto the stack.
   */
  public function push(MigratePreRowSaveEvent $event) {
    $this->stack[] = [];
    $this->debug('Pushed empty array.');
  }

  /**
   * Event callback; no-op outside the context of a migration.
   *
   * @see \Drupal\imagemagick\EventSubscriber\ImagemagickEventSubscriber::ensureSourceLocalPath()
   */
  public function ensureSourceLocalPath(ImagemagickExecutionEvent $event) {
    $current = end($this->stack);
    if ($current === FALSE) {
      // Do nothing; let the imagemagick module handle it.
      $this->debug('Stack empty; passing on ::ensureSourceLocalPath().');
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
    if ($current === NULL) {
      $this->debug('Stack empty... how did you get here!?');
      return;
    }

    $path = $event->getPath();
    $this->debug('Registered {path}', ['path' => $path]);
    $current[] = $path;

    $this->stack[] = $current;
  }

  /**
   * Event callback; pop the stack, cleaning up any contained entries.
   */
  public function pop(MigratePostRowSaveEvent $event) {
    $to_drop = array_pop($this->stack);

    if (!$to_drop) {
      $this->debug('Stack empty or had an empty entry; nothing else to do.');
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
    $this->debug('Deleting chunk: {chunk}', ['chunk' => $chunk]);
    array_map([$this->fileSystem, 'delete'], $chunk);
    $this->debug('Deleted chunk: {chunk}', ['chunk' => $chunk]);
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

          // XXX: Divergence is here, emitting the event instead of registering
          // a shutdown handler.
          $this->eventDispatcher->dispatch(new TempImageEvent($arguments->getSourceLocalPath()), TempImageEvent::EVENT_NAME);
        }
        catch (FileException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }

}
