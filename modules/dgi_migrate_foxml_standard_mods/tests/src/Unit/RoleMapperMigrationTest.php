<?php

namespace Drupal\Tests\dgi_migrate_foxml_standard_mods\Unit;

use Drupal\Tests\UnitTestCase;

use Drupal\dgi_migrate\Plugin\migrate\process\Method;
use Drupal\dgi_migrate\Plugin\migrate\process\Xml\ContextQuery;
use Drupal\dgi_migrate_foxml_standard_mods\Plugin\migrate\process\TypedRelation;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\migrate\process\Callback;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\MultipleValues;

/**
 * Test out the role mapper.
 */
class RoleMapperMigrationTest extends UnitTestCase {

  protected $xpath;
  protected $row;
  protected $plugin;
  protected $migrateExecutable;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $migyaml = <<<EOYAML
source:
  plugin: embedded_data
  data_rows:
    - id: alpha
      xml: |
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
process:
  
EOYAML;

    $internal = [
      'target_id' => [[
        'plugin' => 'default_value',
        'default_value' => 'blah',
      ]],
    ];
    $this->plugin = new TypedRelation(
      [
        'xpath' => '@_xpath',
        'process_values' => TRUE,
        'values' => $internal,
        'mapping' => [
          'relators:cre' => 'Creator (cre)',
          'relators:aft' => 'Author of afterword, colophon, etc. (aft)',
        ],
      ],
      '',
      ['handle_multiples' => FALSE]
    );
    $dom = new \DOMDocument();
    $dom->loadXML(<<<EOXML

EOXML
);
    $this->xpath = new \DOMXPath($dom);
    $this->xpath->registerNamespace('mods', 'http://www.loc.gov/mods/v3');

    $mock_migration = $this->getMockBuilder(MigrationInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $node_array_mock = $this->getMockBuilder(ProcessPluginBase::class)
      ->disableOriginalConstructor()
      ->getMock();

    $node_array_mock->expects($this->any())
      ->method('transform')
      ->will($this->returnValue(iterator_to_array($this->xpath->query('//mods:name'))));

    $node_array_mock->expects($this->any())
      ->method('multiple')
      ->will($this->returnValue(TRUE));

    $mock_migration->expects($this->at(1))
      ->method('getProcessPlugins')
      ->with(NULL)
      ->will($this->returnValue([
        'thing' => [
          $node_array_mock,
          #new MultipleValues([], '', ['handle_multiples' => TRUE]),
          $this->plugin,
        ],
      ]));
    #$mock_migration->expects($this->at(2))
    #  ->method('getProcessPlugins')
    #  ->with($internal)
    #  ->will($this->returnValue([
    #  
    #  ]));

    $mock_id_map = $this->getMockBuilder(MigrateIdMapInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mock_id_map->expects($this->any())
      ->method('setMessage');

    $mock_migration->expects($this->any())
      ->method('getIdMap')
      ->willReturn($mock_id_map);

    $this->migrateExecutable = new MigrateExecutable($mock_migration);
    $this->row = new Row();
    $this->row->setDestinationProperty('_xpath', $this->xpath);
  }

  /**
   * Test our role mapper logic.
   */
  public function testRoleMapper() {
    $this->migrateExecutable->processRow($this->row, NULL);

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
