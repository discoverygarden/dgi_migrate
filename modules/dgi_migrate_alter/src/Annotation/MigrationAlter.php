<?php

namespace Drupal\dgi_migrate_alter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MigrationAlter annotation object.
 *
 * @Annotation
 */
class MigrationAlter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the migration alteration.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the migration alteration.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The migration ID.
   *
   * @var string
   */
  public $migration_id;

}
