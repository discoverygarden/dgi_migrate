<?php

namespace Drupal\dgi_migrate\EventSubscriber;

/**
 * Some event analogs.
 *
 * Some classes do other things with the base events, so let's define a
 * different family of events for use when stubbing.
 */
final class StubMigrateEvents {

  /**
   * Stub pre-import event.
   *
   * @see \Drupal\migrate\Event\MigrateEvents::PRE_IMPORT
   */
  final public const PRE_IMPORT = 'dgi_migrate.stub.pre_import';

  /**
   * Stub post-import event.
   *
   * @see \Drupal\migrate\Event\MigrateEvents::POST_IMPORT
   */
  final public const POST_IMPORT = 'dgi_migrate.stub.post_import';

  /**
   * Stub pre-save event.
   *
   * @see \Drupal\migrate\Event\MigrateEvents::PRE_ROW_SAVE
   */
  final public const PRE_SAVE = 'dgi_migrate.stub.pre_save';

  /**
   * Stub post-save event.
   *
   * @see \Drupal\migrate\Event\MigrateEvents::POST_ROW_SAVE
   */
  final public const POST_SAVE = 'dgi_migrate.stub.post_save';

}
