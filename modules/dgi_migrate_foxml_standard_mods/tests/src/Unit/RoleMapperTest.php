<?php

namespace Drupal\Tests\dgi_migrate_foxml_standard_mods\Unit;

use Drupal\Tests\UnitTestCase;

use Drupal\dgi_migrate_foxml_standard_mods\Plugin\migrate\process\TypedRelation;

/**
 * Test out the role mapper.
 */
class RoleMapperTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->plugin = new TypedRelation(
      [
        // XXX: Just to side-step our validation...
        'xpath' => 'xpath',
        'values' => ['blah'],
        'mapping' => [
          'relators:cre' => 'Creator (cre)',
          'relators:aft' => 'Author of afterword, colophon, etc. (aft)',
        ],
      ],
      '',
      []
    );
    $dom = new \DOMDocument();
    $dom->loadXML(<<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<modsCollection xmlns="http://www.loc.gov/mods/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xlink="http://www.w3.org/1999/xlink"  xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd">
    <mods>
      <name>
        <role>
          <roleTerm type="code">cre</roleTerm>
        </role>
      </name>
      <name>
        <role>
          <roleTerm type="text">Author of afterword, colophon, etc.</roleTerm>
        </role>
      </name>
      <name>
        <role>
          <roleTerm type="code">cre</roleTerm>
          <roleTerm type="text">Creator</roleTerm>
        </role>
      </name>
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

EOXML
);
    $this->xpath = new \DOMXPath($dom);
    $this->xpath->registerNamespace('mods', 'http://www.loc.gov/mods/v3');
  }

  /**
   * Test our role mapper logic.
   *
   * @dataProvider pathProvider
   */
  public function testRoleMapper($path, $expected_roles) {
    $element = $this->xpath->query($path)->item(0);

    $roles = $this->plugin->mapRoles($element, $this->xpath);

    $this->assertSame($expected_roles, array_intersect($expected_roles, $roles), 'Received found the expected role(s).');
  }

  /**
   * Data provider for the test.
   */
  public function pathProvider() {
    $base = '/mods:modsCollection/mods:mods/mods:name';
    return [
      ["{$base}[1]", ['relators:cre']],
      ["{$base}[2]", ['relators:aft']],
      ["{$base}[3]", ['relators:cre']],
      ["{$base}[4]", ['relators:cre', 'relators:aft']],
    ];
  }

}
