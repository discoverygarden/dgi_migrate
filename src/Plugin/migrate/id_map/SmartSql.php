<?php

namespace Drupal\dgi_migrate\Plugin\migrate\id_map;

use Drupal\Component\Plugin\PluginBase;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrationInterface;
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

  // Limit is from MySQL.
  // @see https://dev.mysql.com/doc/refman/8.0/en/identifier-length.html
  const TABLE_NAME_CHARACTER_LIMIT = 63;

  const MAP_TABLE_PREFIX = "migrate_map_";
  const MESSAGE_TABLE_PREFIX = "migrate_message_";

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

}
