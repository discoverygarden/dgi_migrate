<?php

namespace Drupal\Tests\dgi_migrate\Kernel;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
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
  protected function setUp() : void {
    $this->strictConfigSchema = FALSE;
    parent::setUp();
    $this->installConfig('dgi_migrate');
  }

  /**
   * {@inheritDoc}
   */
  public function testDownloadRemoteUri() : void {
    $this->markTestSkipped('Irrelevant to our implementation.');
  }

  /**
   * {@inheritDoc}
   */
  public function testSuccessfulMoves() : void {
    $this->markTestSkipped('Irrelevant to our implementation.');
  }

  /**
   * {@inheritDoc}
   */
  public function testNonExistentSourceFile() : void {
    $source = '/non/existent/file';
    $this->expectException(MigrateException::class);
    // XXX: Different message than the upstream.
    $this->expectExceptionMessage("File /non/existent/file could not be copied to public://foo.jpg");
    $this->doTransform($source, 'public://foo.jpg');
  }

  /**
   * Test base64 decoding procedure.
   */
  public function testBase64Decode() : void {
    $contents = $this->randomString();
    $file_1 = $this->createUri(contents: base64_encode($contents), scheme: 'temporary');
    $destination_path = 'public://file.txt';
    $actual_destination = $this->doTransform("php://filter/resource={$file_1}", $destination_path);
    $this->assertFileExists($destination_path);
    $this->assertEquals($destination_path, $actual_destination);
    $this->assertEquals($contents, file_get_contents($actual_destination));
  }

  /**
   * Test behavior of failed flush to destination.
   */
  public function testFailedFlush() : void {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = $this->container->get('stream_wrapper_manager');
    $stream_wrapper_manager->registerWrapper('fail-flush', FailFlushStream::class, StreamWrapperInterface::NORMAL);
    $file_1 = $this->createUri();
    $destination_uri = 'fail-flush://some/kind-of.txt';
    $this->expectException(MigrateException::class);
    $this->doTransform($file_1, $destination_uri);
  }

  /**
   * Test behavior of failed close of destination.
   */
  public function testFailedClose() : void {
    // XXX: Gap in PHP where stream wrappers are unable to report errors.
    $this->markTestSkipped("PHP's streamWrapper::stream_close() has a void return despite fclose() dealing with a bool.");
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = $this->container->get('stream_wrapper_manager');
    $stream_wrapper_manager->registerWrapper('fail-close', FailCloseStream::class, StreamWrapperInterface::NORMAL);
    $file_1 = $this->createUri();
    $destination_uri = 'fail-close://some/kind-of.txt';
    $this->expectException(MigrateException::class);
    $this->doTransform($file_1, $destination_uri);
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
