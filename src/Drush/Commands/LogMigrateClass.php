<?php

namespace Drupal\dgi_migrate\Drush\Commands;

use Drupal\migrate_tools\Drush9LogMigrateMessage;
use Drupal\migrate_tools\DrushLogMigrateMessage;

if (class_exists(Drush9LogMigrateMessage::class)) {
  /**
   * Alias/subclass for migrate_tools 6.0.5 and older.
   */
  class LogMigrateClass extends Drush9LogMigrateMessage {}
}
else {
  /**
   * Alias/subclass for migrate_tools 6.1.0+.
   */
  class LogMigrateClass extends DrushLogMigrateMessage {}
}
