<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Explode;
use Drupal\migrate\Row;

/**
 * Explodes a string into an array and optional trims the values and filters.
 *
 * Available configuration keys:
 * - filter (optional): Whether to array_filter the results or not, defaults to
 *   TRUE.
 * - trim (optional): Whether to trim the results or not, defaults to TRUE.
 *
 * Available configuration keys from the parent explode plugin:
 * - source: The source string.
 * - limit: (optional)
 *   - If limit is set and positive, the returned array will contain a maximum
 *     of limit elements with the last element containing the rest of string.
 *   - If limit is set and negative, all components except the last -limit are
 *     returned.
 *   - If the limit parameter is zero, then this is treated as 1.
 * - delimiter: The boundary string.
 * - strict: (optional) When this boolean is TRUE, the source should be strictly
 *   a string. If FALSE is passed, the source value is casted to a string before
 *   being split. Also, in this case, the values casting to empty strings are
 *   converted to empty arrays, instead of an array with a single empty string
 *   item ['']. Defaults to TRUE.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.process.explode_trim_filter"
 * )
 *
 * @see: Drupal\migrate\Plugin\migrate\process\Explode.
 */
class ExplodeTrimFilter extends Explode {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $exploded = parent::transform($value, $migrate_executable, $row, $destination_property);

    if (empty($this->configuration['trim']) || $this->configuration['trim']) {
      $exploded = array_map('trim', $exploded);
    }

    if (empty($this->configuration['filter']) || $this->configuration['filter']) {
      $exploded = array_filter($exploded);
    }

    return $exploded;
  }

}
