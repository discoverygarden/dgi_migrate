<?php

namespace Drupal\dgi_migrate_foxml_standard_mods\Plugin\migrate\process;

use Drupal\dgi_migrate\Plugin\migrate\process\TypedRelation as UpstreamTypedRelation;

/**
 * Map to a "typed_relation" field.
 *
 * Example:
 * @code
 * process:
 *   - plugin: dgi_migrate_foxml_standard_mods.typed_relation
 *     source: '@name_node'
 *     xpath: @_xpath_thing
 *     field_name: 'node.islandora_object.field_linked_agent'
 *     default_role: 'relators:asn'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate_foxml_standard_mods.typed_relation"
 * )
 */
class TypedRelation extends DgiTypedRelation {}
