<?php

namespace Drupal\dgi_migrate;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\dgi_migrate\EventSubscriber\StubMigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Wrap import/creation of stubs to ensure events fire for them.
 */
class MigrateStubEventWrapper implements MigrateStubInterface {

  /**
   * Constructor.
   */
  public function __construct(
    protected MigrateStubInterface $inner,
    protected MigrationPluginManagerInterface $migrationPluginManager,
    protected EventDispatcherInterface $eventDispatcher,
    protected MigrateMessageInterface $migrateMessage,
  ) {}

  /**
   * {@inheritDoc}
   */
  public function createStub($migration_id, array $source_ids, array $default_values = []) : array|false {
    $migration = $this->getMigration($migration_id);

    try {
      $this->eventDispatcher->dispatch(new MigrateImportEvent($migration, $this->migrateMessage), StubMigrateEvents::PRE_IMPORT);
      return $this->inner->createStub($migration_id, $source_ids, $default_values);
    }
    finally {
      $this->eventDispatcher->dispatch(new MigrateImportEvent($migration, $this->migrateMessage), StubMigrateEvents::POST_IMPORT);
    }
  }

  /**
   * Helper; load up the migration.
   *
   * Essentially copypasta from the core implementation, since we are also
   * concerned with knowing the migration, in order to be able to emit the
   * event.
   *
   * @param string $migration_id
   *   The ID of the migration, very likely a string.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The loaded migration plugin.
   *
   * @see https://git.drupalcode.org/project/drupal/-/blob/10.5.x/core/modules/migrate/src/MigrateStub.php#L65-72
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMigration(string $migration_id) : MigrationInterface {
    $migrations = $this->migrationPluginManager->createInstances([$migration_id]);
    if (!$migrations) {
      throw new PluginNotFoundException($migration_id);
    }
    if (count($migrations) !== 1) {
      throw new \LogicException(sprintf('Cannot stub derivable migration "%s".  You must specify the id of a specific derivative to stub.', $migration_id));
    }
    $migration = reset($migrations);
    assert($migration instanceof MigrationInterface);
    return $migration;
  }

}
