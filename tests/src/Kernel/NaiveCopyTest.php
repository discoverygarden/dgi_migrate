<?php

namespace Drupal\Tests\dgi_migrate\Unit;

use Drupal\dgi_migrate\Plugin\migrate\process\NaiveFileCopy;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Kernel\process\FileCopyTest;
use GuzzleHttp\Client;

/**
 * Test NaiveCopy plugin scenarios.
 *
 * @see \Drupal\dgi_migrate\Plugin\migrate\process\NaiveFileCopy
 */
class NaiveCopyTest extends FileCopyTest {

  /**
   * {@inheritDoc}
   */
  protected static $modules = ['dgi_migrate', 'migrate', 'system'];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    $this->strictConfigSchema = FALSE;
    parent::setUp();
    $this->installConfig('dgi_migrate');
  }

  /**
   * {@inheritDoc}
   */
  public function testDownloadRemoteUri(): void {
    $this->markTestSkipped('Irrelevant to our implementation.');
  }

  /**
   * {@inheritDoc}
   */
  public function testSuccessfulMoves(): void {
    $this->markTestSkipped('Irrelevant to our implementation.');
  }

  /**
   * {@inheritDoc}
   */
  public function testNonExistentSourceFile(): void {
    $source = '/non/existent/file';
    $this->expectException(MigrateException::class);
    // XXX: Different message than the upstream.
    $this->expectExceptionMessage("File /non/existent/file could not be copied to public://foo.jpg");
    $this->doTransform($source, 'public://foo.jpg');
  }

  /**
   * {@inheritDoc}
   *
   * Copypasta from upstream, swapping out the instantiated plugin class.
   */
  protected function doTransform($source_path, $destination_path, $configuration = []) {
    // Prepare a mock HTTP client.
    $this->container->set('http_client', $this->createMock(Client::class));

    $plugin = NaiveFileCopy::create($this->container, $configuration, 'file_copy', []);
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = new Row([], []);

    return $plugin->transform([$source_path, $destination_path], $executable, $row, 'foo');
  }

}
