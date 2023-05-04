<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Helper for when a specified migration property is missing.
 */
trait MissingBehaviorTrait {

  /**
   * The configured missing behavior.
   *
   * @var string
   */
  protected string $missingBehavior;

  /**
   * The class to which $missingBehavior maps in ::getMissingClassMap().
   *
   * @var string
   */
  protected string $missingClass;

  /**
   * A bit of initialization.
   *
   * Grab our config and lookup the exception to use.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function missingBehaviorInit() : void {
    $this->missingBehavior = $this->configuration[static::getMissingConfigKey()] ?? $this->getDefaultMissingBehavior();

    // XXX: More just for validation, to check that the class exists.
    $this->getMissingClass();
  }

  /**
   * Get the default missing behavior.
   *
   * @return string
   *   One of the keys returned by ::getMissingClassMap().
   */
  protected function getDefaultMissingBehavior() : string {
    return 'abort';
  }

  /**
   * Get the config key used.
   *
   * Ideally, would be a constant; however, traits do not support defining
   * constants.
   *
   * @return string
   *   The key used to hold what missing behavior should be employed.
   */
  final protected static function getMissingConfigKey() : string {
    return 'missing_behavior';
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
  protected function getMissingClass() : string {
    if (!isset($this->missingClass)) {
      if (!isset(static::getMissingClassMap()[$this->missingBehavior])) {
        throw new MigrateException(strtr('Unrecognized "missing_behavior" :input; expecting one of: :valid', [
          ':input' => $this->missingBehavior,
          ':valid' => implode(', ', array_keys(static::getMissingClassMap())),
        ]));
      }
      $this->missingClass = static::getMissingClassMap()[$this->missingBehavior];
    }

    return $this->missingClass;
  }

  /**
   * Instantiate and return the exception.
   *
   * @return \Exception
   *   The appropriate exception.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Propagating from ::getMissingClasse().
   */
  protected function getMissingException($message) : \Exception {
    $class = $this->getMissingClass();
    return new $class($message);
  }

  /**
   * Map tokens to be used in config to classes to instantiate.
   *
   * @return array
   *   An associative array mapping machine names to exception class names.
   */
  protected static function getMissingClassMap() : array {
    return [
      'abort' => MigrateException::class,
      'skip_process' => MigrateSkipProcessException::class,
      'skip_row' => MigrateSkipRowException::class,
    ];
  }

}
