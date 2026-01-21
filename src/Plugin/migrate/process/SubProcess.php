<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Perform subprocessing on an element.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.sub_process"
 * )
 *
 * @code
 * field_thing:
 *   - plugin: dgi_migrate.sub_process
 *     values:
 *       field_one: col_one
 *       field_two: col_two
 *       field_three: "@something_built"
 * field_thing_processed:
 *   - plugin: dgi_migrate.sub_process
 *     process_values: true
 *     parent_row_key: parent_row
 *     parent_value_key: parent_value
 *     values:
 *       field_one:
 *         - plugin: some_process_plugin
 *           source: parent_value
 *           plugin_property: sure_why_not
 *       field_two:
 *         - plugin: get
 *           source: 'parent_row/dest/some_built_thing'
 * @endcode
 *
 * Configuration contents:
 * - values: A mapping of values to use to create. Exact contents
 *   vary based upon the "process_values" flag.
 * - validate: A boolean flag indicating whether the contents of the paragraph
 *   should be validated; defaults to FALSE.
 * - process_values: A boolean flag indicating whether values should be mapped
 *   directly from the current row (false, the default), or if we should kick
 *   of something of a subprocess flow, with nested process plugin
 *   configurations.
 * - propagate_skip: A boolean indicating how a "MigrateSkipRowException" should
 *   be handled when processing a value. TRUE to also skip import of the parent
 *   entity; otherwise, FALSE to skip only those sub-entities throwing the
 *   exception. Defaults to TRUE.
 * - parent_row_key: A string representing a key under which to expose the
 *   the contents of the row to subprocessing with process_values. Defaults to
 *   "parent_row". The contents of the row are split into two keys "source" and
 *   "dest", containing respectively the source and (current) destination values
 *   of the parent row.
 * - parent_value_key: A string representing a key under which to expose the
 *   value received by the "dgi_migrate.sub_process" plugin itself, to make it
 *   available to subprocessing. Defaults to "parent_value".
 * - handle_multiple: Boolean indicating if we should handle an array of arrays
 *   as input.
 * - parent_index: The key added to the row representing the index in the parent
 *   array. Only relevant when "handle_multiple" is TRUE.
 * - output_key: A field from the row which will be used as the index in the
 *   return array. Only relevant when "handle_multiple" is TRUE.
 */
class SubProcess extends ProcessPluginBase {

  /**
   * Values to be processed.
   *
   * @var array
   */
  protected $values;

  /**
   * Flag indicating how we should process items in the "values" config.
   *
   * @var bool
   */
  protected $processValues;

  /**
   * Flag indicating if row skips should be propagated out to the parent.
   *
   * @var bool
   */
  protected $propagateSkip;

  /**
   * The key by which the row should be exposed to the subprocess.
   *
   * @var string
   */
  protected $parentRowKey;

  /**
   * The key by which the value should be exposed to the subprocess.
   *
   * @var string
   */
  protected $parentValueKey;

  /**
   * Flag indicating if we should handle multiple.
   *
   * @var bool
   */
  protected bool $handleMultiple;

  /**
   * The parent index.
   *
   * @var string
   */
  protected string $parentIndex;

  /**
   * The key as which to save the value in the return array.
   *
   * @var string
   */
  protected string $outputKey;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    assert(!empty($this->configuration['values']));
    $this->values = $this->configuration['values'];
    $this->processValues = $this->configuration['process_values'] ?? FALSE;
    $this->propagateSkip = $this->configuration['propagate_skip'] ?? TRUE;
    $this->parentRowKey = $this->configuration['parent_row_key'] ?? 'parent_row';
    $this->parentValueKey = $this->configuration['parent_value_key'] ?? 'parent_value';
    $this->handleMultiple = $this->configuration['handle_multiple'] ?? FALSE;
    $this->parentIndex = $this->configuration['parent_index'] ?? 'parent_index';
    $this->outputKey = $this->configuration['output_key'] ?? $this->parentIndex;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($this->processValues) {
      try {
        return $this->doProcessValues($value, $migrate_executable, $row);
      }
      catch (MigrateSkipRowException $e) {
        if ($this->propagateSkip) {
          throw new MigrateSkipRowException(strtr("Propagating skip from processing \":property\": \n:upstream", [
            ':property' => $destination_property,
            ':upstream' => $e,
          ]));
        }

        // XXX: This code _should_ be inaccessible, based on how ::doProcess()
        // should handle suppressing the exception.
        $migrate_executable->saveMessage(strtr("Non-propagated skip from processing \":property\": \n:upstream", [
          ':property' => $destination_property,
          ':upstream' => $e,
        ]), MigrationInterface::MESSAGE_WARNING);
        return NULL;
      }
      catch (\Exception $e) {
        // Wrap exception with a bit of context.
        throw new \Exception(strtr('Encountered exception when processing ":property".', [
          ':property' => $destination_property,
        ]), 0, $e);
      }
    }
    else {
      return $this->mapValues($migrate_executable, $row);
    }

  }

  /**
   * Map requested fields.
   *
   * @param \Drupal\migrate\MigrateExecutableInterface $executable
   *   The migration exectuable.
   * @param \Drupal\migrate\Row $row
   *   The row object being processed.
   *
   * @return array
   *   An associative array with the mapped values.
   */
  protected function mapValues(MigrateExecutableInterface $executable, Row $row) {
    $mapped = [];

    foreach ($this->values as $key => $property) {
      $mapped[$key] = $row->get($property);
    }

    return $mapped;
  }

  /**
   * Wrap multi-value handling.
   *
   * @param mixed $values
   *   The source value for the plugin.
   * @param \Drupal\migrate\MigrateExecutableInterface $executable
   *   The migration exectuable.
   * @param \Drupal\migrate\Row $row
   *   The row object being processed.
   *
   * @return array
   *   An associative array of processed configuration values.
   */
  protected function doProcessValues($values, MigrateExecutableInterface $executable, Row $row) {
    $to_process = $this->handleMultiple ? $values : ['_programmatic' => $values];

    $to_return = iterator_to_array($this->doProcess($to_process, $executable, $row));

    return $this->handleMultiple ? $to_return : $to_return['_programmatic'];
  }

  /**
   * Process requested values.
   *
   * @param mixed $values
   *   The source value for the plugin.
   * @param \Drupal\migrate\MigrateExecutableInterface $executable
   *   The migration exectuable.
   * @param \Drupal\migrate\Row $row
   *   The row object being processed.
   *
   * @return array
   *   An associative array of processed configuration values.
   */
  protected function doProcess($values, MigrateExecutableInterface $executable, Row $row) {
    foreach ($values as $index => $value) {
      $new_row = new Row([
        $this->parentRowKey => [
          'source' => $row->getSource(),
          'dest' => $row->getDestination(),
        ],
        $this->parentValueKey => $value,
        $this->parentIndex => $index,
      ]);

      try {
        $executable->processRow($new_row, $this->values);

        yield $new_row->get($this->outputKey) => $new_row->getDestination();
      }
      catch (MigrateSkipRowException $e) {
        if ($this->propagateSkip) {
          // Just rethrow it.
          // XXX: Ideally, could throw another and include caught exception as
          // the "previous"; however, it is not presently possible.
          throw $e;
        }
        $executable->saveMessage(strtr('Suppressing :class from processing offset :index. Message: :message', [
          ':class' => MigrateSkipRowException::class,
          ':index' => $index,
          ':message' => $e->getMessage(),
        ]), MigrationInterface::MESSAGE_NOTICE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->handleMultiple;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return [
      // XXX: Our "handle_multiple" config indicates that we both accept and
      // pass-through multiple values... ::multiple() is only dealing with what
      // we _output_... so to more dynamically indicate that we _accept_
      // multiple, dynamically adjust the plugin definition that we provide.
      'handle_multiples' => $this->handleMultiple,
    ] + parent::getPluginDefinition();
  }

}
