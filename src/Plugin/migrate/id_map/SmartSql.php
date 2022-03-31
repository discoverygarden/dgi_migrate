<?php

namespace Drupal\dgi_migrate\Plugin\migrate\id_map;

use Drupal\migrate\EntityFieldDefinitionTrait;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\Entity;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A smart, sql based ID map.
 *
 * Modified solution from https://www.drupal.org/project/smart_sql_idmap.
 *
 * @todo Provide an upgrade path when https://drupal.org/i/2845340 gets fixed.
 *
 * @PluginID("smart_sql")
 */
class SmartSql extends Sql {

  use EntityFieldDefinitionTrait;

  // Limit is from MySQL.
  // @see https://dev.mysql.com/doc/refman/8.0/en/identifier-length.html
  const TABLE_NAME_CHARACTER_LIMIT = 63;

  const MAP_TABLE_PREFIX = "migrate_map_";
  const MESSAGE_TABLE_PREFIX = "migrate_message_";

  /**
   * Flag if we should try to manage orphans and/or their creation.
   *
   * @var bool
   */
  protected bool $manageOrphans;

  /**
   * {@inheritdoc}
   *
   * @see https://drupal.org/i/2845340
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $event_dispatcher);

    // Default generated table names, limited to 63 characters.
    $machine_name = mb_strtolower(str_replace(PluginBase::DERIVATIVE_SEPARATOR, '__', $this->migration->id()));
    $prefix_length = strlen($this->database->tablePrefix());
    $max_length = self::TABLE_NAME_CHARACTER_LIMIT - $prefix_length;

    $map_table_name = self::MAP_TABLE_PREFIX . $machine_name;
    $message_table_name = self::MESSAGE_TABLE_PREFIX . $machine_name;

    $map_table_name_within_limit = strlen($map_table_name) <= $max_length;
    $message_table_name_within_limit = strlen($message_table_name) <= $max_length;

    if ($map_table_name_within_limit && $message_table_name_within_limit) {
      // If the name does not exceed the 63 character limit we can use the same
      // logic as in Drupal core.
      $this->mapTableName = $map_table_name;
      $this->messageTableName = $message_table_name;
    }
    else {
      // It's possible that we used truncated tables in the past that were
      // still unique, and we should continue to use them if they exist.
      $truncated_map_table_name = mb_substr(self::MAP_TABLE_PREFIX . $map_table_name, 0, $max_length);
      $truncated_message_table_name = mb_substr(self::MESSAGE_TABLE_PREFIX . $message_table_name, 0, $max_length);

      $truncated_map_table_exists = $this->database->schema()->tableExists($truncated_map_table_name);
      $truncated_message_table_exists = $this->database->schema()->tableExists($truncated_message_table_name);

      if ($truncated_map_table_exists && $truncated_message_table_exists) {
        // If the tables exist use them, otherwise we use generated hash
        // solution below.
        $this->mapTableName = $truncated_map_table_name;
        $this->messageTableName = $truncated_message_table_name;
      }
      else {
        // Otherwise we use the name to generate a unique
        // hash which we then use for the table names.
        $md5 = md5($machine_name);
        $this->mapTableName = mb_substr(self::MAP_TABLE_PREFIX . $md5, 0, $max_length);
        $this->messageTableName = mb_substr(self::MESSAGE_TABLE_PREFIX . $md5, 0, $max_length);
        // The above is safe as md5 results in 32 characters and the prefix
        // length does not exceed 16 characters, so 48 total. which is less
        // than the max of 63. If the database prefix is used it does decrease
        // the efficacy of the md5 if it pushes the length beyond the limit,
        // but we don't use it.
      }
    }

    $this->manageOrphans = $this->configuration['manage_orphans'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return parent::create($container, $configuration, $plugin_id, $plugin_definition, $migration)
      ->setEntityTypeManager($container->get('entity_type.manager'));
  }

  /**
   * Service setter, for DI.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service to be used.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @see https://drupal.org/i/3227549
   * @see https://drupal.org/i/3227660
   */
  public function getRowByDestination(array $destination_id_values) {
    $missing_destination_keys = array_diff(
      array_keys($this->destinationIdFields()),
      array_keys($destination_id_values)
    );
    // Fix for https://drupal.org/i/3227549.
    $result = $missing_destination_keys
      ? NULL
      : parent::getRowByDestination($destination_id_values);

    if ($result && $this->manageOrphans) {
      $this->doManageOrphans($result, $destination_id_values);
    }

    $core_major_minor = implode(
      '.',
      [
        explode('.', \Drupal::VERSION)[0],
        explode('.', \Drupal::VERSION)[1],
      ]
    );
    if (version_compare($core_major_minor, '9.3', 'ge')) {
      return $result ?? [];
    }
    // Fix for https://drupal.org/i/3227549 and workaround for
    // https://drupal.org/i/3227660.
    return $result ? $result : ['rollback_action' => 99999];
  }

  /**
   * Helper; manage rollback actions for entities existing between migrations.
   *
   * There's four cases, where we are dealing with things between migrations:
   * - an entity created in one (A), but then later referenced in another
   *   migration (B), should be kept.
   * - an entity created in A, but never referenced by another migration should
   *   be deleted
   * - an entity that existed prior to and was referenced in a migration should
   *   be kept.
   *
   * Note that, there could be some orphans _created_ due to the first and third
   * points; say, migrations A and B are run, B references a term from A, and
   * then both are rolled back. When A is rolled back, there's still the
   * reference from B, but then when B is rolled back, it has it down as having
   * existed prior, so the term would be left intact, despite all referencing
   * entities having been removed from the system.
   *
   * @param array $result
   *   Reference to the row data being manipulated.
   * @param array $destination_id_values
   *   Array of destination key info.
   */
  protected function doManageOrphans(array &$result, array $destination_id_values) {
    if ($result['rollback_action'] != MigrateIdMapInterface::ROLLBACK_DELETE) {
      // If things are ::ROLLBACK_PRESERVE'd, we have no mechanism by which to
      // decide if we should take control, so... leave things intact.
      return;
    }

    $destination = $this->migration->getDestinationPlugin();
    if (!($destination instanceof Entity)) {
      // Nothing to do, as this migration deals with something other than
      // entities? Or... at least does so without using the base class.
      return;
    }

    // Load up the entity.
    $id_type = static::getEntityTypeId($destination->getPluginId());
    $entity = $this->entityTypeManager
      ->getStorage($id_type)
      ->load(reset($destination_id_values));

    if (!$entity) {
      // Failed to load... deleted by something else?
      return;
    }

    if ($this->entityTypeManager->hasHandler($id_type, 'entity_reference_integrity')) {
      // Do the check.
      $has_dependents = $this->entityTypeManager
        ->getHandler($id_type, 'entity_reference_integrity')
        ->hasDependents($entity);
    }
    else {
      throw new MigrateException("Type '{$id_type}' missing an 'entity_reference_integrity' handler, despite being told to manage orphans.");
    }

    if ($has_dependents) {
      // There's dependents, so keep the entity around.
      $result['rollback_action'] = MigrateIdMapInterface::ROLLBACK_PRESERVE;
    }
  }

}
