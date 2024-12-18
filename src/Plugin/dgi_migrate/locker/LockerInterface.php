<?php

namespace Drupal\dgi_migrate\Plugin\dgi_migrate\locker;

/**
 * Interface for locker plugins.
 *
 * Intended to be very similar to flock(), without being strictly bound to
 * files.
 */
interface LockerInterface {

  /**
   * Acquire lock of given name.
   *
   * @param string $name
   *   The name/ID to lock.
   * @param int $mode
   *   The mode with which to lock, as a bit-field, expecting the use of the
   *   LOCK_EX, LOCK_SH and LOCK_NB constants.
   * @param bool $would_block
   *   If LOCK_NB was in $mode, flag if we failed to acquire the lock due to it
   *   being held by another process.
   *
   * @return bool
   *   TRUE if we acquired the lock; otherwise, FALSE.
   */
  public function acquireLock(string $name, int $mode = LOCK_EX, bool &$would_block = FALSE) : bool;

  /**
   * Release lock of the given name.
   *
   * @param string $name
   *   The name/ID of the lock to release.
   *
   * @return bool
   *   TRUE if we released the lock; otherwise, FALSE (if we did not hold the
   *   given lock?).
   */
  public function releaseLock(string $name) : bool;

  /**
   * Acquire control lock.
   *
   * @return bool
   *   TRUE if it was successfully acquired; otherwise, FALSE.
   */
  public function acquireControl() : bool;

  /**
   * Release control lock.
   *
   * @return bool
   *   TRUE if it was successfully released; otherwise, FALSE.
   */
  public function releaseControl() : bool;

}
