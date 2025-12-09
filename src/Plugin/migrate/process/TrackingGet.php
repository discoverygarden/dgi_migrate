<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Track where things came from.
 *
 * Effective looking to augment the core `get` plugin to track what destination
 * properties had source properties.
 *
 * @see \Drupal\migrate\Plugin\migrate\process\Get
 */
class TrackingGet extends ProcessPluginBase implements MigrateProcessInterface, ContainerFactoryPluginInterface {

  const PROPERTY_NAME = __CLASS__ . '_tracker';

  protected MigrateProcessInterface $originalPlugin;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) : self {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    /** @var \Drupal\migrate\Plugin\MigratePluginManagerInterface $process_plugin_manager */
    $process_plugin_manager = $container->get('plugin.manager.migrate.process');
    $instance->originalPlugin = $process_plugin_manager->createInstance('dgi_migrate_original_get', $configuration, $migration);
    $instance->migration = $migration;

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $tracker = $row->hasDestinationProperty(static::PROPERTY_NAME) ?
      $row->getDestinationProperty(static::PROPERTY_NAME) :
      [];

    $source = $this->configuration['source'];
    $properties = is_string($source) ? [$source] : $source;

    $tracker[$destination_property] = array_any($properties, function (string $property) use ($row, $tracker) {
      // Adapted from the Row class.
      // @see https://git.drupalcode.org/project/drupal/-/blob/4f22ed87387ed92e5b1c8be1814de354706f6623/core/modules/migrate/src/Row.php#L345-355
      $is_source = TRUE;
      if (str_starts_with($property, '@')) {
        $property = preg_replace_callback('/^(@?)((?:@@)*)([^@]|$)/', function ($matches) use (&$is_source) {
          // If there are an odd number of @ in the beginning, it's a
          // destination.
          $is_source = empty($matches[1]);
          // Remove the possible escaping and do not lose the terminating
          // non-@ either.
          return str_replace('@@', '@', $matches[2]) . $matches[3];
        }, $property);
      }
      return $is_source ?
        $row->hasSourceProperty($property) :
        $tracker[$property];
    });
    $row->setDestinationProperty(static::PROPERTY_NAME, $tracker);

    return $this->originalPlugin->transform($value, $migrate_executable, $row, $destination_property);
  }

  /**
   * Produce a filtered copy of the row, filtered according to the tracked data.
   *
   * @param \Drupal\migrate\Row $row
   *   The row to filter.
   *
   * @return \Drupal\migrate\Row
   *   The filtered row.
   */
  public static function filterRow(Row $row) : Row {
    if (!$row->hasDestinationProperty(static::PROPERTY_NAME)) {
      // No tracker; do not do anything.
      return $row;
    }

    $tracker = $row->getDestinationProperty(static::PROPERTY_NAME);

    $copy = $row->cloneWithoutDestination();
    foreach ($row->getRawDestination() as $property => $value) {
      $copy->setDestinationProperty($property, $value);
    }
    // If a source column was present, mark absent properties accordingly.
    foreach (array_keys(array_filter($tracker)) as $property) {
      if (!$copy->hasDestinationProperty($property)) {
        $copy->setEmptyDestinationProperty($property);
      }
    }
    return $copy;
  }

}
