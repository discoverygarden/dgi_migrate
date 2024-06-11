<?php

namespace Drupal\Tests\dgi_migrate_foxml_standard_mods\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;
use Symfony\Component\Yaml\Yaml;

/**
 * Test out the role mapper.
 *
 * @group dgi_migrate_foxml_standard_mods
 */
class RoleMapperMigrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate',
    'dgi_migrate',
    'dgi_migrate_foxml_standard_mods',
    'migrate_plus',
  ];

  /**
   * Stub row.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * Stub migrate executable.
   *
   * @var \Drupal\migrate\MigrateExecutableInterfaace
   */
  protected $migrateExecutable;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $migyaml = <<<EOYAML
source:
  plugin: embedded_data
  data_rows: []
  ids:
    name:
      type: string
process:
  _xpath:
    - plugin: dgi_migrate.process.xml.domstring
      source: xml
    - plugin: dgi_migrate.process.xml.xpath
      namespaces:
        mods: 'http://www.loc.gov/mods/v3'
        xsi: 'http://www.w3.org/2001/XMLSchema-instance'
        xlink: 'http://www.w3.org/1999/xlink'
  thing:
    - plugin: dgi_migrate.method
      source: '@_xpath'
      method: query
      args: ['//mods:name']
    - plugin: dgi_migrate_foxml_standard_mods.typed_relation
      xpath: '@_xpath'
      default_role: 'relators:asn'
      mapping:
        'relators:cre': 'Creator (cre)'
        'relators:aft': 'Author of afterword, colophon, etc. (aft)'
      process_values: true
      values:
        target_id:
          - plugin: default_value
            default_value: blah
    - plugin: single_value
    - plugin: callback
      callable: array_merge
destination:
  plugin: entity:node
  default_bundle: islandora_object
EOYAML;
    $xml = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<modsCollection xmlns="http://www.loc.gov/mods/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xlink="http://www.w3.org/1999/xlink"  xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd">
  <mods>
    <!-- Parse a code -->
    <name>
      <role>
        <roleTerm type="code">cre</roleTerm>
      </role>
    </name>
    <!-- Parse a text -->
    <name>
      <role>
        <roleTerm type="text">Author of afterword, colophon, etc.</roleTerm>
      </role>
    </name>
    <!-- Only return one role, given both code and text in one. -->
    <name>
      <role>
        <roleTerm type="code">cre</roleTerm>
        <roleTerm type="text">Creator</roleTerm>
      </role>
    </name>
    <!-- Return the combination of roles. -->
    <name>
      <role>
        <roleTerm type="code">aft</roleTerm>
      </role>
      <role>
        <roleTerm type="text">Creator</roleTerm>
      </role>
    </name>
  </mods>
</modsCollection>
EOXML;

    $stub_migration = \Drupal::service('plugin.manager.migration')->createStubMigration(Yaml::parse($migyaml));

    $this->migrateExecutable = new MigrateExecutable($stub_migration);
    $this->row = new Row(['xml' => $xml]);
  }

  /**
   * Test our role mapper logic.
   */
  public function testRoleMapper() {
    $this->migrateExecutable->processRow($this->row);

    $result = $this->row->getDestinationProperty('thing');

    $this->assertSame(
      [
        [
          'rel_type' => 'relators:cre',
          'target_id' => 'blah',
        ],
        [
          'rel_type' => 'relators:aft',
          'target_id' => 'blah',
        ],
        [
          'rel_type' => 'relators:cre',
          'target_id' => 'blah',
        ],
        [
          'rel_type' => 'relators:aft',
          'target_id' => 'blah',
        ],
        [
          'rel_type' => 'relators:cre',
          'target_id' => 'blah',
        ],
      ],
      $result
    );
  }

}
