<?php

namespace Drupal\dgi_migrate\Plugin\dgi_migrate\locker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\dgi_migrate\Attribute\Locker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * PostgreSQL advisory locking plugin.
 */
#[Locker('pgsql_advisory_locking')]
class PgsqlAdvisoryLocking extends PluginBase implements LockerInterface, ContainerFactoryPluginInterface {

  /**
   * Track held exclusive locks.
   *
   * An associative array mapping lock IDs to modes.
   *
   * @var int[]
   */
  protected array $exclusiveLocks = [];

  /**
   * Track held shared locks.
   *
   * An associative array mapping lock IDs to modes.
   *
   * @var int[]
   */
  protected array $sharedLocks = [];

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) : static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function acquireLock(string $name, int $mode = LOCK_EX, bool &$would_block = FALSE): bool {
    $lock_id = static::toLockId($name);
    if ($mode === LOCK_EX) {
      $this->database->query(
        'SELECT pg_advisory_lock(:lock_id);',
        [
          ':lock_id' => $lock_id,
        ],
      );
      $this->exclusiveLocks[$lock_id]++;
      return TRUE;
    }
    if ($mode === LOCK_SH) {
      $this->database->query(
        'SELECT pg_advisory_lock_shared(:lock_id);',
        [
          ':lock_id' => $lock_id,
        ],
      );
      $this->sharedLocks[$lock_id]++;
      return TRUE;
    }
    if ($mode === LOCK_EX | LOCK_NB) {
      $result = $this->database->query(
        'SELECT pg_try_advisory_lock(:lock_id);',
        [
          ':lock_id' => $lock_id,
        ],
      )?->fetchField();
      if ($result) {
        $this->exclusiveLocks[$lock_id]++;
      }
      return $result;
    }
    if ($mode === LOCK_SH | LOCK_NB) {
      $result = $this->database->query(
        'SELECT pg_try_advisory_lock_shared(:lock_id);',
        [
          ':lock_id' => $lock_id,
        ],
      )?->fetchField();
      if ($result) {
        $this->sharedLocks[$lock_id]++;
      }
      return $result;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function releaseLock(string $name): bool {
    $lock_id = static::toLockId($name);

    $shared = $this->releaseSharedLocks($lock_id);
    $exclusive = $this->releaseExclusiveLocks($lock_id);

    return $shared || $exclusive;
  }

  /**
   * PostgreSQL deal with (big) integers for its locks, so let's map things.
   *
   * Somewhat adapted from https://stackoverflow.com/a/9812029.
   *
   * @param string $name
   *   The lock name to map.
   *
   * @return int
   *   An ID to use.
   */
  protected static function toLockId(string $name) : int {
    return hexdec(substr(md5($name), 0, 16));
  }

  /**
   * Release target exclusive lock.
   *
   * @param int $lock_id
   *   Mapped ID of the lock to release.
   *
   * @return bool
   *   TRUE if we released without issue; otherwise, FALSE if we thought we had
   *   to release it too many times.
   */
  protected function releaseExclusiveLocks(int $lock_id) : bool {
    $results = [];
    $occurrences = $this->exclusiveLocks[$lock_id] ?? 0;
    while ($occurrences > 0) {
      $results[] = (bool) $this->database->query(
        'SELECT pg_advisory_unlock(:lock_id);',
        [
          ':lock_id' => $lock_id,
        ],
      )?->fetchField();
      $occurrences--;
    }
    unset($this->exclusiveLocks[$lock_id]);
    return !empty($results) && !in_array(FALSE, $results, TRUE);
  }

  /**
   * Release target shared lock.
   *
   * @param int $lock_id
   *   Mapped ID of the lock to release.
   *
   * @return bool
   *   TRUE if we released without issue; otherwise, FALSE if we thought we had
   *   to release it too many times.
   */
  protected function releaseSharedLocks(int $lock_id) : bool {
    $results = [];
    $occurrences = $this->sharedLocks[$lock_id] ?? 0;
    while ($occurrences > 0) {
      $results[] = (bool) $this->database->query(
        'SELECT pg_advisory_unlock_shared(:lock_id);',
        [
          ':lock_id' => $lock_id,
        ],
      )?->fetchField();
      $occurrences--;
    }
    unset($this->sharedLocks[$lock_id]);
    return !empty($results) && !in_array(FALSE, $results, TRUE);
  }

}
