<?php

namespace Drupal\dgi_migrate\Utility;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use const iqb\stream\SUBSTREAM_SCHEME;

/**
 * Read-only stream wrapper supporting access inside of streams.
 *
 * Wraps an iqb.substream stream wrapper to allow the stream from which the
 * substream to be pulled to be specified as a URI.
 */
class Substream extends ReadOnlyStream {
  const SCHEME = 'dgi-migrate.substream';

  /**
   * The target resource from which to extract the substream.
   *
   * @var resource
   */
  protected $target = NULL;

  /**
   * A stream from iqb.substream resource.
   *
   * @var resource
   */
  protected $proxy = NULL;

  /**
   * Helper; parse the passed path.
   *
   * @param string $path
   *   The path to parse.
   *
   * @return string[]
   *   An array of strings representing:
   *   - the starting offset inside the target stream
   *   - the number of bytes to include, after the offset
   *   - the target resource URI
   */
  protected function parsePath($path) {
    $matches = [];
    $sep = '/';
    $pattern = implode('', [
      "{$sep}^",
      preg_quote(static::SCHEME . '://', $sep),
      '(\d+)',
      preg_quote(':', $sep),
      '(\d+)',
      preg_quote('/', $sep),
      "(.+)\${$sep}",
    ]);
    preg_match($pattern, $path, $matches);
    return array_slice($matches, 1);
  }

  /**
   * Helper; generate a URI parsable by this stream wrapper.
   *
   * @param string $target
   *   URI of the target resource.
   * @param int $start
   *   The starting offset inside of the resource.
   * @param int $length
   *   The number of bytes to include after the offset.
   *
   * @return string
   *   A URI pointing at the substream using this stream wrapper.
   */
  public static function format($target, $start, $length) {
    return strtr('!scheme://!start:!length/!target', [
      '!scheme' => static::SCHEME,
      '!start' => $start,
      '!length' => $length,
      '!target' => $target,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return ReadOnlyStream::HIDDEN;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'DGI migrate substream';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return 'Substreams; a reference to a part of another file.';
  }

  /**
   * Helper; throw the "Not implemented" exception in a common way.
   *
   * @throws \Exception
   *   If we're called.
   */
  protected function throwNotImplemented() {
    throw new \Exception('Not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $this->throwNotImplemented();
  }

  /**
   * {@inheritdoc}
   */
  public function realpath() {
    $this->throwNotImplemented();
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    $this->throwNotImplemented();
  }

  /**
   * {@inheritdoc}
   *
   * XXX: We're dealing with implementing an interface with names we don't like.
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function stream_close() {
    fclose($this->proxy);
    fclose($this->target);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_eof() {
    return feof($this->proxy);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_open($path, $mode, $options, &$opened_path) {
    list($start, $length, $target) = $this->parsePath($path);
    $this->target = fopen($target, $mode);
    if (!$this->target) {
      // Failed to open the source stream.
      return FALSE;
    }

    $target_uri = implode('', [
      SUBSTREAM_SCHEME,
      '://',
      $start,
      ':',
      $length,
      '/',
      (int) $this->target,
    ]);
    $this->proxy = fopen($target_uri, $mode);
    if (!$this->proxy) {
      // Failed to open the proxy stream; close the target and return such.
      fclose($this->target);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_read($count) {
    return fread($this->proxy, $count);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_seek($offset, $whence = \SEEK_SET) {
    return fseek($this->proxy, $whence);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_tell() {
    return ftell($this->proxy);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_stat() {
    return fstat($this->proxy);
  }

  /**
   * {@inheritdoc}
   */
  public function dir_closedir() {
    $this->throwNotImplemented();
  }

  /**
   * {@inheritdoc}
   */
  public function dir_opendir($path, $options) {
    $this->throwNotImplemented();
  }

  /**
   * {@inheritdoc}
   */
  public function dir_readdir() {
    $this->throwNotImplemented();
  }

  /**
   * {@inheritdoc}
   */
  public function dir_rewinddir() {
    $this->throwNotImplemented();
  }

  /**
   * {@inheritdoc}
   */
  public function stream_cast($cast_as) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_set_option($options, $arg1, $arg2) {
    $this->throwNotImplemented();
  }

  /**
   * {@inheritdoc}
   */
  public function url_stat($path, $flags) {
    list(, $length, $target) = $this->parsePath($path);
    $stat = [
      7 => (int) $length,
      'size' => (int) $length,
    ];

    $target_stat = stat($target);

    if ($target_stat) {
      return $stat + $target_stat;
    }
    else {
      throw new \Exception('Failed to stat target !file', [
        '!file' => $target,
      ]);
    }
  }// phpcs:enable

}
