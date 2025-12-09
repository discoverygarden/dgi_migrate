<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
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

  /**
   * The name of the destination property in which to build our tracking info.
   */
  const PROPERTY_NAME = __CLASS__ . '_tracker';

  /**
   * Instance of the plugin we are overriding.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected MigrateProcessInterface $wrappedPlugin;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) : self {
    /** @var \Drupal\migrate\Plugin\MigratePluginManagerInterface $process_plugin_manager */
    $process_plugin_manager = $container->get('plugin.manager.migrate.process');
    return (new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    ))
      ->setWrappedPlugin($process_plugin_manager->createInstance('dgi_migrate_original_get', $configuration, $migration));
  }

  /**
   * Set the "get" plugin instance we are to wrap.
   *
   * @param \Drupal\migrate\Plugin\MigrateProcessInterface $to_wrap
   *   The original "get" plugin instance to wrap.
   *
   * @return $this
   *   Fluent API.
   */
  public function setWrappedPlugin(MigrateProcessInterface $to_wrap) : self {
    $this->wrappedPlugin = $to_wrap;
    return $this;
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

    $tracker[$destination_property] = static::any($properties, static function (string $property) use ($row, $tracker) {
      // Adapted from the Row class.
      // @see https://git.drupalcode.org/project/drupal/-/blob/4f22ed87387ed92e5b1c8be1814de354706f6623/core/modules/migrate/src/Row.php#L345-355
      $is_source = TRUE;
      if (str_starts_with($property, '@')) {
        $property = preg_replace_callback('/^(@?)((?:@@)*)([^@]|$)/', static function ($matches) use (&$is_source) {
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
        ($tracker[$property] ?? $row->hasDestinationProperty($property));
    });
    $row->setDestinationProperty(static::PROPERTY_NAME, $tracker);

    return $this->wrappedPlugin->transform($value, $migrate_executable, $row, $destination_property);
  }

  /**
   * Avoid dependency on PHP 8.4 polyfill for `\array_any()`.
   *
   * @param array $values
   *   Values to test.
   * @param callable $callback
   *   The callback with which to test.
   *
   * @return bool
   *   TRUE if an item returned TRUE; otherwise, FALSE.
   *
   * @see \array_any()
   */
  private static function any(array $values, callable $callback) : bool {
    if (function_exists('array_any')) {
      return array_any($values, $callback);
    }

    // Adapted from polyfill.
    // @see https://github.com/symfony/polyfill-php84/blob/d8ced4d875142b6a7426000426b8abc631d6b191/Php84.php#L90-L99
    foreach ($values as $key => $value) {
      if ($callback($value, $key)) {
        return TRUE;
      }
    }
    return FALSE;
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

    // Remove our tracking info from the row, as it has served its purpose.
    $copy->removeDestinationProperty(static::PROPERTY_NAME);

    return $copy;
  }

}
