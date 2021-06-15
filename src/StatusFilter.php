<?php

namespace Drupal\dgi_migrate;

use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Id map status filter iterator.
 */
class StatusFilter extends \FilterIterator {

  /**
   * Mapping of human-friendly machine names for to represent the constants.
   */
  const STATUSES = [
    'imported' => MigrateIdMapInterface::STATUS_IMPORTED,
    'needs_update' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    'ignored' => MigrateIdMapInterface::STATUS_IGNORED,
    'failed' => MigrateIdMapInterface::STATUS_FAILED,
  ];

  /**
   * Array of MigrateIdMapInterface::STATUS_* values to which to constrain.
   *
   * @var int[]
   */
  protected $statuses;

  /**
   * Constructor.
   *
   * @param \Iterator $child
   *   An upstream iterator/ID map which we are filtering.
   * @param array $statuses
   *   An array of MigrateIdMapInterface::STATUS_* values to which we will
   *   constrain iteration.
   */
  public function __construct(\Iterator $child, array $statuses) {
    parent::__construct($child);

    $this->statuses = $statuses;
  }

  /**
   * {@inheritdoc}
   */
  public function accept() {
    return empty($this->statuses) || in_array($this->getInnerIterator()->current()['source_row_status'], $this->statuses);
  }

  /**
   * Helper; map human-friendly machine names back to values from constants.
   *
   * @param string $input
   *   A comma-separated set of the keys of the STATUSES constant.
   *
   * @return int[]
   *   The array as per $statuses.
   *
   * @throws \InvalidArgumentException
   *   If we encounter a value which we cannot/do not map.
   */
  public static function mapStatuses($input) {
    $to_map = array_flip(array_map('strtolower', array_map('trim', explode(',', $input))));

    $diff = array_diff_key($to_map, static::STATUSES);
    if ($diff) {
      throw new \InvalidArgumentException(strtr('Encountered unrecognized statuses (:statuses); expected a comma-separated subset of (:allowed)', [
        ':statuses' => implode(', ', array_keys($diff)),
        ':allowed' => implode(', ', array_keys(static::STATUSES)),
      ]));
    }

    return array_intersect_key(static::STATUSES, $to_map);
  }

}
