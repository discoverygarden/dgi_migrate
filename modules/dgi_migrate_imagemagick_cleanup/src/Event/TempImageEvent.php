<?php

namespace Drupal\dgi_migrate_imagemagick_cleanup\Event;

use Symfony\Component\EventDispatcher\Event;

class TempImageEvent extends Event {

  const EVENT_NAME = 'dgi_migrate_imagemagick_cleanup.temp_image';

  protected $path;

  public function __construct($path) {
    $this->path = $path;
  }

  public function getPath() {
    return $this->path;
  }

}
