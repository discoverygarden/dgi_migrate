<?php

namespace Drupal\Tests\dgi_migrate\Unit;

use Drupal\dgi_migrate\Plugin\migrate\process\TrackingGet;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Test our TrackingGet class.
 *
 * @group dgi_migrate
 */
class TrackingGetTest extends UnitTestCase {

  /**
   * Mock migration executable.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface
   */
  protected MigrateExecutableInterface $mockExecutable;

  /**
   * A randomly-generated property name for use in the given test.
   *
   * @var string
   */
  protected string $testPropName;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mockExecutable = $this->createStub(MigrateExecutableInterface::class);
    $this->testPropName = $this->randomMachineName();
  }

  /**
   * Helper; build out the plugin instance.
   *
   * @param array $configuration
   *   Param to use during instantiation.
   *
   * @return \Drupal\dgi_migrate\Plugin\migrate\process\TrackingGet
   *   The plugin instance.
   */
  protected function getInstance(array $configuration) : TrackingGet {
    return (new TrackingGet($configuration, '', []))
      ->setWrappedPlugin($this->createStub(MigrateProcessInterface::class));
  }

  /**
   * Test the presence of a source property is reflected.
   */
  public function testPresentSource() : void {
    $row = (new Row(
      [
        'a' => 'a',
        'b' => 'b',
      ],
      ['a' => 'a'],
    ))
      ->freezeSource();

    $this->getInstance(['source' => 'b'])
      ->transform(NULL, $this->mockExecutable, $row, $this->testPropName);
    $row->setDestinationProperty($this->testPropName, 'b');
    $this->assertTrue($row->hasDestinationProperty(TrackingGet::PROPERTY_NAME));
    $this->assertTrue($row->getDestinationProperty(TrackingGet::PROPERTY_NAME)[$this->testPropName]);

    $filtered = TrackingGet::filterRow($row);
    $this->assertNotContains($this->testPropName, $filtered->getEmptyDestinationProperties());
  }

  /**
   * Test the presence of a source property that does "skip process".
   */
  public function testPresentEmptySourceSkip() : void {
    $row = (new Row(
      [
        'a' => 'a',
        'b' => '',
      ],
      ['a' => 'a'],
    ))
      ->freezeSource();

    $this->getInstance(['source' => 'b'])
      ->transform(NULL, $this->mockExecutable, $row, $this->testPropName);
    $row->setEmptyDestinationProperty($this->testPropName);
    $this->assertTrue($row->hasDestinationProperty(TrackingGet::PROPERTY_NAME));
    $this->assertTrue($row->getDestinationProperty(TrackingGet::PROPERTY_NAME)[$this->testPropName]);

    $filtered = TrackingGet::filterRow($row);
    $this->assertContains($this->testPropName, $filtered->getEmptyDestinationProperties());
  }

  /**
   * Test the presence of a source property that gets passed-through.
   */
  public function testPresentEmptySourcePass() : void {
    $row = (new Row(
      [
        'a' => 'a',
        'b' => '',
      ],
      ['a' => 'a'],
    ))
      ->freezeSource();

    $this->getInstance(['source' => 'b'])
      ->transform(NULL, $this->mockExecutable, $row, $this->testPropName);
    $row->setDestinationProperty($this->testPropName, '');
    $this->assertTrue($row->hasDestinationProperty(TrackingGet::PROPERTY_NAME));
    $this->assertTrue($row->getDestinationProperty(TrackingGet::PROPERTY_NAME)[$this->testPropName]);

    $filtered = TrackingGet::filterRow($row);
    $this->assertEquals('', $filtered->getDestinationProperty($this->testPropName));
    $this->assertNotContains($this->testPropName, $filtered->getEmptyDestinationProperties());
  }

  /**
   * Test the absence of a source property is reflected.
   */
  public function testAbsentSource() : void {
    $row = (new Row(
      ['a' => 'a'],
      ['a' => 'a'],
    ))
      ->freezeSource();

    $this->getInstance(['source' => 'b'])
      ->transform(NULL, $this->mockExecutable, $row, $this->testPropName);
    // Approximate behavior MigrateExecutable.
    // @see https://git.drupalcode.org/project/drupal/-/blob/10.5.x/core/modules/migrate/src/MigrateExecutable.php?ref_type=heads#L476
    $row->setEmptyDestinationProperty($this->testPropName);
    $this->assertTrue($row->hasDestinationProperty(TrackingGet::PROPERTY_NAME));
    $this->assertFalse($row->getDestinationProperty(TrackingGet::PROPERTY_NAME)[$this->testPropName]);

    $filtered = TrackingGet::filterRow($row);
    $this->assertNull($filtered->getDestinationProperty($this->testPropName));
    $this->assertNotContains($this->testPropName, $filtered->getEmptyDestinationProperties());
  }

  /**
   * Test transitive property existence.
   */
  public function testTransitiveExistence() : void {
    $row = (new Row(
      [
        'a' => 'a',
        'b' => 'b',
      ],
      ['a' => 'a'],
    ))
      ->freezeSource();

    $transitive_prop = $this->randomMachineName();

    $this->getInstance(['source' => 'b'])
      ->transform(NULL, $this->mockExecutable, $row, $this->testPropName);
    $row->setDestinationProperty($this->testPropName, 'b');
    $this->getInstance(['source' => "@{$this->testPropName}"])
      ->transform(NULL, $this->mockExecutable, $row, $transitive_prop);
    $row->setDestinationProperty($transitive_prop, 'b');
    $this->assertTrue($row->getDestinationProperty(TrackingGet::PROPERTY_NAME)[$transitive_prop]);

    $filtered = TrackingGet::filterRow($row);
    $this->assertNotContains($transitive_prop, $filtered->getEmptyDestinationProperties());
  }

  /**
   * Test transitive property absence.
   */
  public function testTransitiveAbsence() : void {
    $row = (new Row(
      ['a' => 'a'],
      ['a' => 'a'],
    ))
      ->freezeSource();

    $transitive_prop = $this->randomMachineName();

    $this->getInstance(['source' => 'b'])
      ->transform(NULL, $this->mockExecutable, $row, $this->testPropName);
    // Approximate behavior MigrateExecutable.
    // @see https://git.drupalcode.org/project/drupal/-/blob/10.5.x/core/modules/migrate/src/MigrateExecutable.php?ref_type=heads#L476
    $row->setEmptyDestinationProperty($this->testPropName);
    $this->getInstance(['source' => "@{$this->testPropName}"])
      ->transform(NULL, $this->mockExecutable, $row, $transitive_prop);
    $row->setEmptyDestinationProperty($transitive_prop);
    $this->assertFalse($row->getDestinationProperty(TrackingGet::PROPERTY_NAME)[$transitive_prop]);

    $filtered = TrackingGet::filterRow($row);
    $this->assertNull($filtered->getDestinationProperty($transitive_prop));
    $this->assertNotContains($transitive_prop, $filtered->getEmptyDestinationProperties());
  }

}
