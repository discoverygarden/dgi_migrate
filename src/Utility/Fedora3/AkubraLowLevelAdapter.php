<?php

namespace Drupal\dgi_migrate\Utilty\Fedora3;

class AkubraLowLevelAdapter implements LowLevelAdapterInterface {

  public function __construct($base_path, $pattern = '##') {
    $this->basePath = $base_path;
    $this->pattern = $pattern;
  }

  /**
   * 
   */
  public function dereference($id) {
    // Structure like: "the:pid+DSID+DSID.0"
    // Need: "{base_path}/{hash_pattern}/{id}"

    $slashed = str_replace('+', '/', $id);
    $full = "info:fedora/$slashed";
    $hash = md5($full);

    $pattern_offset = 0;
    $hash_offset = 0;
    $subbed = $this->pattern;

    while (($pattern_offset = strpos($subbed, '#', $pattern_offset)) !== FALSE) {
      $subbed[$pattern_offset] = $hash[$hash_offset++];
    }

    $encoded = rawurlencode($full);

    return "$base_path/$subbed/$encoded";
  }

}
