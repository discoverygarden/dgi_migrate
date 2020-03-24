<?php

namespace Drupal\dgi_migrate\Utility;

class SubstreamFilter extends \php_user_filter {
  public $filtername;
  public $params;

  protected $start, $length;

  public function filter($in, $out, &$consumed, $closing) {
    while ($bucket = stream_bucket_make_writeable($in)) {
      $delta = $bucket->datalen + $consumed;
      if ($consumed < $this->start) {
        if ($delta > $this->start) {
          // Crossing the boundary; slice and dice.
          
        }
        else {
          // Before the block of stuff to move; skip it.
        }
        $consumed += $bucket->datalen;
        continue;
      }
      if ($consumed > $this->start && $delta < $this->end) {
        // Inside the block on both sides.
        $consumed += $bucket->datalen;
        stream_bucket_append($out, $bucket);
        return PSFS_PASS_ON;
      }
    }
  }

  public function onClose() {
  }

  public function onCreate() {
    list(, $this->start, $this->end) = explode('.', $this->filtername)
  }
}
