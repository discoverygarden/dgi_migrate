<?php

declare(strict_types=1);

namespace Drupal\Tests\dgi_migrate\Unit\process;

use Drupal\dgi_migrate\Plugin\migrate\process\TrackingGet;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the TrackingGet process plugin.
 *
 * @see \Drupal\Tests\migrate\Unit\process\GetTest
 *
 * @group dgi_migrate
 */
class AdaptedTrackingGetTest extends MigrateProcessTestCase {

  /**
   * Tests the Get plugin when source is a string.
   */
  public function testTransformSourceString(): void {
    $this->row->expects($this->once())
      ->method('get')
      ->with('test')
      ->willReturn('source_value');
    $this->plugin = (new TrackingGet($conf = ['source' => 'test'], '', []))
      ->setWrappedPlugin(new Get($conf, '', []));
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('source_value', $value);
  }

  /**
   * Tests the Get plugin when source is an array.
   */
  public function testTransformSourceArray(): void {
    $map = [
      'test1' => 'source_value1',
      'test2' => 'source_value2',
    ];
    $this->plugin = (new TrackingGet($conf = ['source' => ['test1', 'test2']], '', []))
      ->setWrappedPlugin(new Get($conf, '', []));
    $this->row->expects($this->exactly(2))
      ->method('get')
      ->willReturnCallback(function ($argument) use ($map) {
        return $map[$argument];
      });
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(['source_value1', 'source_value2'], $value);
  }

  /**
   * Tests the Get plugin when source is a string pointing to destination.
   */
  public function testTransformSourceStringAt(): void {
    $this->row->expects($this->once())
      ->method('get')
      ->with('@@test')
      ->willReturn('source_value');
    $this->plugin = (new TrackingGet($conf = ['source' => '@@test'], '', []))
      ->setWrappedPlugin(new Get($conf, '', []));
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('source_value', $value);
  }

  /**
   * Tests the Get plugin when source is an array pointing to destination.
   */
  public function testTransformSourceArrayAt(): void {
    $map = [
      'test1' => 'source_value1',
      '@@test2' => 'source_value2',
      '@@test3' => 'source_value3',
      'test4' => 'source_value4',
    ];
    $this->plugin = (new TrackingGet($conf = ['source' => ['test1', '@@test2', '@@test3', 'test4']], '', []))
      ->setWrappedPlugin(new Get($conf, '', []));
    $this->row->expects($this->exactly(4))
      ->method('get')
      ->willReturnCallback(function ($argument) use ($map) {
        return $map[$argument];
      });
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(['source_value1', 'source_value2', 'source_value3', 'source_value4'], $value);
  }

  /**
   * Tests the Get plugin when source has integer values.
   *
   * @dataProvider integerValuesDataProvider
   */
  public function testIntegerValues($source, $expected_value): void {
    $this->row->expects($this->atMost(2))
      ->method('get')
      ->willReturnOnConsecutiveCalls('val1', 'val2');

    $this->plugin = (new TrackingGet($conf = ['source' => $source], '', []))
      ->setWrappedPlugin(new Get($conf, '', []));
    $return = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected_value, $return);
  }

  /**
   * Provides data for the successful lookup test.
   *
   * @return array
   *   Provided test data.
   */
  public static function integerValuesDataProvider() : array {
    return [
      [
        'source' => [0 => 0, 1 => 'test'],
        'expected_value' => [0 => 'val1', 1 => 'val2'],
      ],
      [
        'source' => [FALSE],
        'expected_value' => [NULL],
      ],
      [
        'source' => [NULL],
        'expected_value' => [NULL],
      ],
    ];
  }

  /**
   * Tests the Get plugin for syntax errors by creating a prophecy of the class.
   *
   * An example of a syntax error is "Invalid tag_line detected".
   */
  public function testPluginSyntax(): void {
    $this->assertNotNull($this->prophesize(Get::class));
  }

}
