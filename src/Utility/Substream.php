<?php

namespace Drupal\dgi_migrate\Utility;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use const iqb\stream\SUBSTREAM_SCHEME;

class Substream extends ReadOnlyStream {
  const SCHEME = 'dgi-migrate.substream';
  protected $target = NULL;
  protected $proxy = NULL;

  public function stream_close() {
    fclose($this->proxy);
    fclose($this->target);
  }

  public function stream_eof() {
    return feof($this->proxy);
  }

  public function stream_open($path, $mode, $options, &$opened_path) {
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
    list(, $start, $length, $target) = $matches;
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

  public function stream_read($count) {
    return fread($this->proxy, $count);
  }

  public function stream_seek($offset, $whence = \SEEK_SET) {
    return fseek($this->proxy, $whence);
  }

  public function stream_tell() {
    return ftell($this->proxy);
  }

  public function stream_stat() {
    return fstat($this->proxy);
  }

  public static function format($target, $start, $length) {
    return strtr('!scheme://!start:!length/!target', [
      '!scheme' => static::SCHEME,
      '!start' => $start,
      '!length' => $length,
      '!target' => $target,
    ]);
  }

  public static function getType() {
    return ReadOnlyStream::HIDDEN;
  }

  public function getName() {
    return 'DGI migrate substream';
  }

  public function getDescription() {
    return 'Substreams; a reference to a part of another file.';
  }

  protected function throwNotImplemented() { throw new Exception('Not implemented'); }
  public function getExternalUrl() { $this->throwNotImplemented(); }
  public function realpath() { $this->throwNotImplemented(); }
  public function dirname($uri = NULL) { $this->throwNotImplemented(); }
  public function dir_closedir() { $this->throwNotImplemented(); }
  public function dir_opendir($path, $options) { $this->throwNotImplemented(); }
  public function dir_readdir() { $this->throwNotImplemented(); }
  public function dir_rewinddir() { $this->throwNotImplemented(); }
  public function stream_cast($cast_as) { $this->throwNotImplemented(); }
  public function stream_set_option($options, $arg1, $arg2) { $this->throwNotImplemented(); }
  public function url_stat($path, $flags) {
    // XXX: Nothing to report.
    return [];
  }
}
