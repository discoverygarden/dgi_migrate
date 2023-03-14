<?php

namespace Drupal\dgi_migrate_imagemagick_cleanup\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Temp image event description.
 */
class TempImageEvent extends Event {

  const EVENT_NAME = 'dgi_migrate_imagemagick_cleanup.temp_image';

  /**
   * The path of the temp image.
   *
   * @var string
   */
  protected $path;

  /**
   * Constructor.
   *
   * @param string $path
   *   The path/URI associated with this event.
   */
  public function __construct($path) {
    $this->path = $path;
  }

  /**
   * Get the path.
   */
  public function getPath() {
    return $this->path;
  }

}
