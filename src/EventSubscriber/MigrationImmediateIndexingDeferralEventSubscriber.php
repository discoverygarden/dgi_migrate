<?php

namespace Drupal\dgi_migrate\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\search_api\SearchApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Suppress immediate indexing during migrations.
 */
class MigrationImmediateIndexingDeferralEventSubscriber implements EventSubscriberInterface {

  use AutowireTrait;
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Memoized indexes.
   *
   * @var \Drupal\search_api\IndexInterface[]
   */
  protected array $indexes;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Flag, do suppression if TRUE; otherwise, do not suppress direct indexing.
   *
   * @var bool
   */
  protected bool $doSuppression;

  /**
   * Constructor.
   */
  public function __construct(
    #[Autowire(service: 'entity_type.manager')]
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'config.factory')]
    ConfigFactoryInterface $configFactory,
    #[Autowire(service: 'messenger')]
    protected MessengerInterface $messenger,
    #[Autowire(service: 'logger.factory')]
    protected ?LoggerChannelFactoryInterface $loggerChannelFactory = NULL,
    ?LoggerInterface $logger = NULL,
    ?bool $doSuppression = NULL,
    protected bool $debug = FALSE,
  ) {
    if (!$logger && !$this->loggerChannelFactory) {
      throw new \InvalidArgumentException('$loggerChannelFactory or $logger must be passed.');
    }
    if ($logger) {
      $this->logger = $logger;
    }
    if (!isset($this->logger)) {
      $this->logger = $this->loggerChannelFactory->get(static::class);
    }

    if (!$this->entityTypeManager->hasDefinition('search_api_index')) {
      $this->debug('Index entity definition does not appear to exist.');
      $this->doSuppression = FALSE;
    }
    else {
      $env_value = getenv('DGI_MIGRATE_SUPPRESS_DIRECT_INDEXING_DURING_MIGRATIONS');
      if (!in_array($env_value, [FALSE, ''], TRUE)) {
        $this->doSuppression = $env_value === 'true';
      }
      if (!isset($this->doSuppression) && isset($doSuppression)) {
        $this->doSuppression = $doSuppression;
      }
      if (!isset($this->doSuppression)) {
        $this->doSuppression = $configFactory->get('dgi_migrate.settings')
          ->get('suppress_direct_indexing_during_migrations');
      }
    }

  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() : array {
    return [
      MigrateEvents::PRE_IMPORT => 'startBatchTracking',
      MigrateEvents::POST_IMPORT => 'stopBatchTracking',
      MigrateEvents::PRE_ROW_SAVE => 'startBatchTracking',
      MigrateEvents::POST_ROW_SAVE => 'stopBatchTracking',
      MigrateEvents::PRE_ROLLBACK => 'startBatchTracking',
      MigrateEvents::POST_ROLLBACK => 'stopBatchTracking',
      MigrateEvents::PRE_ROW_DELETE => 'startBatchTracking',
      MigrateEvents::POST_ROW_DELETE => 'stopBatchTracking',
      StubMigrateEvents::PRE_IMPORT => 'startBatchTracking',
      StubMigrateEvents::POST_IMPORT => 'stopBatchTracking',
      StubMigrateEvents::PRE_SAVE => 'startBatchTracking',
      StubMigrateEvents::POST_SAVE => 'stopBatchTracking',
    ];
  }

  /**
   * Wrap debug log messages such that we might suppress them.
   *
   * Would be nice if LoggerInterface supported additional verbosity levels such
   * as "trace" and whatnot, but whatever.
   *
   * @param string|\Stringable $message
   *   The message to be logged.
   * @param array $context
   *   Context for the log message.
   */
  protected function debug(string|\Stringable $message, array $context = []) : void {
    if ($this->debug) {
      $this->logger->debug($message, $context);
    }
  }

  /**
   * Helper; lazily gather indexes to deal with.
   *
   * @return \Drupal\search_api\IndexInterface[]|null
   *   Active indexes configured to "index_directly". NULL if $populate is FALSE
   *   and $this->indexes has not been populated by other means.
   */
  protected function getIndexes(bool $populate = TRUE) : ?array {
    if ($populate && !isset($this->indexes)) {
      $this->indexes = $this->entityTypeManager->getStorage('search_api_index')->loadByProperties([
        'status' => TRUE,
        'options.index_directly' => TRUE,
      ]);

      $this->debug('Found {index_count} index(es): {indexes}', [
        'indexes' => implode(', ', array_keys($this->indexes)),
        'index_count' => count($this->indexes),
      ]);
    }

    return $this->indexes ?? NULL;
  }

  /**
   * Start suppressing immediate indexing.
   */
  public function startBatchTracking($event) : void {
    if (!$this->doSuppression) {
      $this->debug('Suppression is disabled; not starting for {event}.', [
        'event' => get_class($event),
      ]);
      return;
    }
    $this->debug('Starting batch tracking on event: {event}', [
      'event' => get_class($event),
    ]);

    $indexes = $this->getIndexes();
    foreach ($indexes as $index) {
      $this->debug('Starting batch tracking on {index_id}.', [
        'index_id' => $index->id(),
      ]);
      $index->startBatchTracking();
      $this->debug('Started batch tracking on {index_id}.', [
        'index_id' => $index->id(),
      ]);
    }

    if (count($indexes) > 0) {
      $this->messenger->addStatus(
        $this->t('Search API indexing was deferred during the recent migration import/rollback batch operation; items may not show correctly while indexes are not up-to-date.'),
        repeat: FALSE,
      );
    }
  }

  /**
   * Stop suppressing immediate indexing.
   */
  public function stopBatchTracking($event) : void {
    if (!$this->doSuppression) {
      $this->debug('Suppression is disabled; not stopping for {event}.', [
        'event' => get_class($event),
      ]);
      return;
    }
    $this->debug('Stopping batch tracking on event: {event}', [
      'event' => get_class($event),
    ]);

    $indexes = $this->getIndexes(FALSE);
    if ($indexes === NULL) {
      $this->debug('Indexes not set; post-event received in different process from pre-event, no need to stop.');
      return;
    }

    foreach ($indexes as $index) {
      try {
        $this->debug('Stopping batch tracking on {index_id}.', [
          'index_id' => $index->id(),
        ]);
        $index->stopBatchTracking();
        $this->debug('Stopped batch tracking on {index_id}.', [
          'index_id' => $index->id(),
        ]);
      }
      catch (SearchApiException) {
        $this->logger->warning('Attempted to stop batch tracking on index not doing batch tracking ({index_id}); suppressing exception.', [
          'index_id' => $index->id(),
        ]);
      }
    }
  }

}
