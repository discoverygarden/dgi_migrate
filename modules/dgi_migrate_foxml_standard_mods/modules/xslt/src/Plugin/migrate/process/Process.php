<?php

namespace Drupal\dgi_migrate_foxml_standard_mods_xslt\Plugin\migrate\process;

use Drupal\dgi_saxon_helper_migrate\Plugin\migrate\process\Saxon;
use Drupal\dgi_saxon_helper\TransformerInterface;

use Drupal\Core\Url;

/**
 * Wrapper to help perform _our_ transformation.
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate_foxml_standard_mods_xslt.process"
 * )
 */
class Process extends Saxon {

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransformerInterface $service) {
    $configuration['path'] = $configuration['path'] ?? Url::fromRoute('dgi_migrate_foxml_standard_mods_xslt.download', [], ['absolute' => TRUE])->toString();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $service);
  }

}
