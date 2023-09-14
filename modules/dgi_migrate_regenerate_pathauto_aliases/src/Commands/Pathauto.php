<?php

namespace Drupal\dgi_migrate_regenerate_pathauto_aliases\Commands;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drush\Commands\DrushCommands;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\pathauto\PathautoState;
use Psr\Log\LoggerInterface;

/**
 * Re-generates missing pathauto aliases.
 */
class Pathauto extends DrushCommands {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Pathauto Generator.
   *
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $pathautoGenerator;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator
   *   Service that generates aliases via pathauto.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger to which to log.
   */
  public function __construct(PathautoGeneratorInterface $pathauto_generator, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, LoggerInterface $logger) {
    $this->pathautoGenerator = $pathauto_generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->logger = $logger;
  }

  /**
   * Re-generate path aliases for nodes that had it disabled.
   *
   * @option bundle The "bundle" of the node type to derive.
   *
   * @command dgi_migrate_regenerate_pathauto_aliases:enable-missing-path-aliases
   * @aliases dmrpa:empa
   *
   * @validate-module-enabled islandora_drush_utils
   * @islandora-drush-utils-user-wrap
   */
  public function regenerate($options = [
    'bundle' => 'islandora_object',
  ]) {

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('node');
    if (!isset($bundles[$options['bundle']])) {
      throw new \Exception("The provided 'bundle' ({$options['bundle']}) is not valid for nodes.");
    }
    $batch = [
      'title' => $this->t('Enabling and generating missing path aliases...'),
      'operations' => [[[$this, 'pathAliasBatch'], [$options['bundle']]]],
    ];
    drush_op('batch_set', $batch);
    drush_op('drush_backend_batch_process');
  }

  /**
   * Batch for generating path aliases for nodes that were disabled.
   *
   * @param string $bundle
   *   The bundle of the node being generated.
   * @param array|\DrushBatchContext $context
   *   Batch context.
   */
  public function pathAliasBatch($bundle, &$context) {
    $sandbox =& $context['sandbox'];

    $node_storage = $this->entityTypeManager->getStorage('node');
    $base_query = $node_storage->getQuery()->condition('type', $bundle)
      ->accessCheck();
    if (!isset($sandbox['total'])) {
      $count_query = clone $base_query;
      $sandbox['total'] = $count_query->count()->execute();
      if ($sandbox['total'] === 0) {
        $context['message'] = $this->t('Batch empty.');
        $context['finished'] = 1;
        return;
      }
      $sandbox['last_nid'] = FALSE;
      $sandbox['completed'] = 0;
    }

    if ($sandbox['last_nid']) {
      $base_query->condition('nid', $sandbox['last_nid'], '>');
    }

    $base_query->sort('nid');
    $base_query->range(0, 10);
    foreach ($base_query->execute() as $result) {
      try {
        $sandbox['last_nid'] = $result;
        $node = $node_storage->load($result);
        if (!$node) {
          $this->logger->debug('Failed to load node {node}; skipping.', [
            'node' => $result,
          ]);
          continue;
        }
        if ($node->path->pathauto !== PathautoState::CREATE) {
          $node->path->pathauto = PathautoState::CREATE;
        }
        $this->pathautoGenerator->updateEntityAlias($node, 'bulkupdate', ['message' => TRUE]);
        $context['message'] = $this->t('Generating path alias for node @node.', [
          '@node' => $node->id(),
        ]);
        $this->logger->debug('Path alias generated for {node}.', [
          'node' => $node->id(),
        ]);
      }
      catch (\Exception $e) {
        $this->logger->exception('Encountered an exception: {exception}', [
          'exception' => $e,
        ]);
      }
      $sandbox['completed']++;
      $context['finished'] = $sandbox['completed'] / $sandbox['total'];
    }
  }

}
