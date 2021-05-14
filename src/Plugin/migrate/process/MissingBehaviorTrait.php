<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

trait MissingBehaviorTrait {
  const CLASSMAP = [
    'abort' => MigrateException::class,
    'skip_process' => MigrateSkipProcessException::class,
    'skip_row' => MigrateSkipRowException::class,
  ];
  const CONFIG_KEY = 'missing_behaviour':

  protected $missingBehavior = NULL;
  protected $missingClass = NULL;

  protected function missingBehaviorInit() {
    $this->missingBehavior = $this->configuration[static::CONFIG_KEY] ?? $this->getDefaultMissingBehavior();

    // XXX: More just for validation, to check that the class exists.
    $this->getMissingClass();
  }

  protected function getDefaultMissingBehavior() {
    return 'abort';
  }

  /**
   * Get the name of the exception class to use when the index is missing.
   *
   * @return string
   *   The name of the class to use.
   *
   * @throws \Drupal\migrate\MigrateException
   *   If the indicated behavior does not appear to be valid.
   */
  protected function getMissingClass() {
    if (!isset($this->missingClass)) {
      if (!isset(static::CLASSMAP[$this->missingBehavior])) {
        throw new MigrateException(strtr('Unrecognized "missing_behavior" :input; expecting one of: :valid', [
          ':input' => $this->missingBehavior,
          ':valid' => implode(', ', array_keys(static::CLASSMAP)),
        ]));
      }
      $this->missingClass = static::CLASSMAP[$this->missingBehavior];
    }

    return $this->missingClass;
  }

  /**
   * Instantiate and return the exception.
   *
   * @return \Exception
   *   The appropriate exception.
   */
  protected function getMissingException($message) {
    $class = $this->getMissingClass();
    return new $class($message);
  }

}
