<?php

namespace Drupal\dgi_migrate\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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

  public function __construct(
    #[Autowire(service: 'entity_type.manager')]
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'config.factory')]
    ConfigFactoryInterface $configFactory,
    #[Autowire(service: 'logger.factory')]
    protected ?LoggerChannelFactoryInterface $loggerChannelFactory = NULL,
    ?LoggerInterface $logger = NULL,
    ?bool $doSuppression = NULL,
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

    $env_value = getenv('DGI_MIGRATE_SUPPRESS_DIRECT_INDEXING_DURING_MIGRATIONS');
    if (!in_array($env_value, [FALSE, ''], TRUE)) {
      $this->doSuppression = $env_value === 'true';
    }
    if (!isset($this->doSuppression) && isset($doSuppression)) {
      $this->doSuppression = $doSuppression;
    }
    if (!isset($this->doSuppression)) {
      $this->doSuppression = $configFactory->get('dgi_migrate.settings')->get('suppress_direct_indexing_during_migrations');
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
    ];
  }

  /**
   * Start suppressing immediate indexing.
   */
  public function startBatchTracking() : void {
    if (!$this->doSuppression) {
      return;
    }
    $this->indexes ??= $this->entityTypeManager->getStorage('index')->loadByProperties([
      'status' => TRUE,
      'options.index_directly' => TRUE,
    ]);

    foreach ($this->indexes as $index) {
      $this->logger->debug('Starting batch tracking on {index_id}.', [
        'index_id' => $index->id(),
      ]);
      $index->startBatchTracking();
      $this->logger->debug('Started batch tracking on {index_id}.', [
        'index_id' => $index->id(),
      ]);
    }
  }

  /**
   * Stop suppressing immediate indexing.
   */
  public function stopBatchTracking($event) : void {
    if (!$this->doSuppression) {
      return;
    }
    foreach ($this->indexes as $index) {
      try {
        $this->logger->debug('Stopping batch tracking on {index_id}.', [
          'index_id' => $index->id(),
        ]);
        $index->stopBatchTracking();
        $this->logger->debug('Stopped batch tracking on {index_id}.', [
          'index_id' => $index->id(),
        ]);
      }
      catch (SearchApiException) {
        $this->logger->warning('Attempted to stop batch tracking on index not doing batch tracking; suppressing exception.', [
          'index_id' => $index->id(),
        ]);
      }
    }
  }

}
