<?php

/**
 * @file
 * Migration alterations for the dgis_nodes migration.
 */

use Drupal\dgi_migrate_foxml_standard_mods_xslt\Form\Settings;

use Drupal\Core\Url;

use Symfony\Component\Yaml\Yaml;

/**
 * Migration alterations callback.
 *
 * @param array $mig
 *   A reference to the migration to be altered.
 */
function _dgi_migrate_foxml_standard_mods_xslt_dgis_nodes_migration_alterations(array &$mig) {
  $proc = &$mig['process'];

  $config = \Drupal::config(Settings::CONFIG);

  // XXX: This (dependency addition) does not appear to actually do anything,
  // but is included for consistency. We _have_ implemented an event subscriber
  // to clear the migration cache when our config changes.
  $mig['dependencies']['config'][] = $config->getName();

  if ($config->get('xslt')) {
    $proc['_mods_xpath'] = Yaml::parse(<<<EOI
- plugin: dgi_migrate.subindex
  index: 'MODS'
  source: '@_node_foxml_parsed'
  missing_behavior: skip_process
- plugin: dgi_migrate.method
  method: getUri
- plugin: callback
  callable: file_get_contents
- plugin: dgi_migrate_foxml_standard_mods_xslt.process
- plugin: dgi_migrate.process.xml.domstring
- plugin: dgi_migrate.process.xml.xpath
  namespaces:
    mods: 'http://www.loc.gov/mods/v3'
    xsi: 'http://www.w3.org/2001/XMLSchema-instance'
    xlink: 'http://www.w3.org/1999/xlink'
EOI
    );
  }
  else {
    \Drupal::logger('dgi_migrate_foxml_standard_mods_xslt')->notice('XSLT module enabled; however, no XSLT configured.');
  }

}
