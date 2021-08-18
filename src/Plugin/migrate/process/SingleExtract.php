<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\Extract;

/**
 * Extract from a single value.
 *
 * Identical to the core "extract" plugin, except avoiding the "handle_multiple"
 * bit of the annotation, 'cause we want to handle individual arrays in a bigger
 * array.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.single_extract",
 * )
 */
class SingleExtract extends Extract {}
