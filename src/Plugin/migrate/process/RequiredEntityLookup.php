<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stronger entity lookup.
 *
 * See parent entity_lookup plugin for most config keys. Additionally, we add:
 * - missing_behavior: One of "abort", "skip_process" or "skip_row", as per the
 *   MissingBehaviorTrait. Defaults here to "skip_row".
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.required_entity_lookup",
 *   handle_multiples = TRUE
 * )
 */
class RequiredEntityLookup extends EntityLookup {

  use MissingBehaviorTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, ?MigrationInterface $migration = NULL) {
    $instance = parent::create($container, $configuration, $pluginId, $pluginDefinition, $migration);

    $instance->missingBehaviorInit();

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultMissingBehavior() {
    return 'skip_row';
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $result = parent::transform($value, $migrate_executable, $row, $destination_property);

    if (!$result) {
      throw $this->getMissingException(strtr('Failed to find lookup entity value ":value" for property ":destination_property".', [
        ':value' => var_export($value, TRUE),
        ':destination_property' => $destination_property,
      ]));
    }

    return $result;
  }

}
