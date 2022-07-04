<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Call a method on an object.
 *
 * Example:
 * @code
 * process:
 *   - plugin: dgi_migrate.method
 *     source: '@some_object'
 *     method: someMethod
 *     args:
 *       - alpha
 *       - bravo
 * @endcode
 * @code
 * process:
 *   alpha:
 *   - plugin: get
 *     source: something
 *   thing:
 *   - plugin: dgi_migrate.method
 *     source: '@some_object'
 *     method: someMethod
 *     dereference_args: true
 *     args:
 *       - "@alpha"
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.method"
 * )
 */
class Method extends ProcessPluginBase {

  /**
   * The method of the object to target.
   *
   * May be null if the object itself is invokeable.
   *
   * @var string|null
   */
  protected ?string $method;

  /**
   * The arguments for the method call.
   *
   * @var array
   */
  protected array $args;

  /**
   * Flag to handle dereferencing $args from the row being processed.
   *
   * @var bool
   */
  protected bool $dereferenceArgs;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->method = $this->configuration['method'] ?? NULL;
    $this->args = $this->configuration['arg'] ?? [];
    if (!is_array($this->args)) {
      throw new MigrateException('Arguments must be provided as an array.');
    }
    $this->dereferenceArgs = $this->configuration['dereference_args'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_object($value)) {
      $type = gettype($value);
      throw new MigrateException("Input should be an object. (Attempting to add value to {$destination_property} of type {$type})");
    }

    // A singular object _can_ be callable, if it implements ::__invoke().
    $callable = $this->method ? [$value, $this->method] : $value;

    if (!is_callable($callable)) {
      throw new MigrateException("The specified does not appear to be callable.");
    }

    return call_user_func_array($callable, $this->getArgs($row));
  }

  /**
   * Helper; handle the dereferencing, if necessary.
   */
  protected function getArgs(Row $row) {
    return ($this->args && $this->dereferenceArgs) ?
      $row->getMultiple($this->args) :
      $this->args;
  }

}
