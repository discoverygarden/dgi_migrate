<?php

namespace Drupal\dgi_migrate;

/**
 * Batch exception typification.
 */
class MigrateBatchException extends \Exception {

  /**
   * Store the "finished" value, if we want to provide it.
   *
   * @var float|null
   */
  protected $finished;

  /**
   * Constructor.
   */
  public function __construct($message = '', $finished = NULL, $previous = NULL) {
    parent::__construct($message, NULL, $previous);

    $this->finished = $finished;
  }

  /**
   * Get the stored "finished" value.
   *
   * @return float|null
   *   The finished value, if provided; otherwise, NULL... which means that it
   *   is up to the handler to calculate.
   */
  public function getFinished() {
    return $this->finished;
  }

}
