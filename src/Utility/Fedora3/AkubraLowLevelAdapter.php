<?php

namespace Drupal\dgi_migrate\Utilty\Fedora3;

class AkubraLowLevelAdapter implements LowLevelAdapterInterface {

  /**
   * The datastreamStore path.
   *
   * @var string
   */
  protected $basePath;

  /**
   * The pattern used in Akubra of hash content in the path.
   *
   * @var string
   */
  protected $pattern;

  /**
   * Constructor.
   */
  public function __construct($base_path, $pattern = '##') {
    $this->basePath = $base_path;
    $this->pattern = $pattern;
  }

  /**
   * {@inheritdoc}
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

    $encoded = strtr(rawurlencode($full), [
      '_' => '%5F',
    ]);

    return "{$this->basePath}/$subbed/$encoded";
  }

}
