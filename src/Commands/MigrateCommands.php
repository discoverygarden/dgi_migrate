<?php

namespace Drupal\dgi_migrate\Commands;

use Drupal\migrate_tools\Commands\MigrateToolsCommands;
use Drupal\dgi_migrate\MigrateBatchExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Migration command.
 */
class MigrateCommands extends MigrateToolsCommands {

  /**
   * Perform one or more migration processes.
   *
   * XXX: Somewhat silly, but... unsure off the top of my head to nicely extend
   * another command.
   *
   * @param string $migration_names
   *   ID of migration(s) to import. Delimit multiple using commas.
   * @param array $options
   *   Additional options for the command.
   *
   * @command dgi-migrate:import
   *
   * @aliases migrate:batch-import
   *
   * @option all Process all migrations.
   * @option group A comma-separated list of migration groups to import
   * @option tag Name of the migration tag to import
   * @option limit Limit on the number of items to process in each migration
   * @option feedback Frequency of progress messages, in items processed
   * @option idlist Comma-separated list of IDs to import
   * @option idlist-delimiter The delimiter for records
   * @option update  In addition to processing unprocessed items from the
   *   source, update previously-imported items with the current data
   * @option force Force an operation to run, even if all dependencies are not
   *   satisfied
   * @option continue-on-failure When a migration fails, continue processing
   *   remaining migrations.
   * @option execute-dependencies Execute all dependent migrations first.
   * @option skip-progress-bar Skip displaying a progress bar.
   * @option sync Sync source and destination. Delete destination records that
   *   do not exist in the source.
   *
   * @default $options []
   * @usage migrate:batch-import --all
   *   Perform all migrations
   * @usage migrate:batch-import --group=beer
   *   Import all migrations in the beer group
   * @usage migrate:batch-import --tag=user
   *   Import all migrations with the user tag
   * @usage migrate:batch-import --group=beer --tag=user
   *   Import all migrations in the beer group and with the user tag
   * @usage migrate:batch-import beer_term,beer_node
   *   Import new terms and nodes
   * @usage migrate:batch-import beer_user --limit=2
   *   Import no more than 2 users
   * @usage migrate:batch-import beer_user --idlist=5
   *   Import the user record with source ID 5
   * @usage migrate:batch-import beer_node_revision --idlist=1:2,2:3,3:5
   *   Import the node revision record with source IDs [1,2], [2,3], and [3,5]
   *
   * @validate-module-enabled migrate_tools,dgi_migrate
   * @dgi-i8-helper-user-wrap
   *
   * @throws \Exception
   *   If there are not enough parameters to the command.
   */
  public function batchImport($migration_names = '', array $options = [
    'all' => FALSE,
    'group' => self::REQ,
    'tag' => self::REQ,
    'limit' => self::REQ,
    'feedback' => self::REQ,
    'idlist' => self::REQ,
    'idlist-delimiter' => self::DEFAULT_ID_LIST_DELIMITER,
    'update' => FALSE,
    'force' => FALSE,
    'continue-on-failure' => FALSE,
    'execute-dependencies' => FALSE,
    'skip-progress-bar' => FALSE,
    'sync' => FALSE,
  ]) {
    return parent::import($migration_names, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeMigration(MigrationInterface $migration, $migration_id, array $options = []) {
    // Keep track of all migrations run during this command so the same
    // migration is not run multiple times.
    static $executed_migrations = [];

    if ($options['execute-dependencies']) {
      $definition = $migration->getPluginDefinition();
      $required_migrations = $definition['requirements'] ?? [];
      $required_migrations = array_filter($required_migrations, function ($value) use ($executed_migrations) {
        return !isset($executed_migrations[$value]);
      });

      if (!empty($required_migrations)) {
        $manager = $this->migrationPluginManager;
        $required_migrations = $manager->createInstances($required_migrations);
        $dependency_options = array_merge($options, ['is_dependency' => TRUE]);
        array_walk($required_migrations, [$this, __FUNCTION__], $dependency_options);
        $executed_migrations += $required_migrations;
      }
    }
    if ($options['sync']) {
      $migration->set('syncSource', TRUE);
    }
    if ($options['skip-progress-bar']) {
      $migration->set('skipProgressBar', TRUE);
    }
    if ($options['continue-on-failure']) {
      $migration->set('continueOnFailure', TRUE);
    }
    if ($options['force']) {
      $migration->set('requirements', []);
    }
    if ($options['update']) {
      if (!$options['idlist']) {
        $migration->getIdMap()->prepareUpdate();
      }
      else {
        $source_id_values_list = MigrateTools::buildIdList($options);
        $keys = array_keys($migration->getSourcePlugin()->getIds());
        foreach ($source_id_values_list as $source_id_values) {
          $migration->getIdMap()->setUpdate(array_combine($keys, $source_id_values));
        }
      }
    }

    $executable = new MigrateBatchExecutable($migration, $this->getMigrateMessage(), $options);
    // drush_op() provides --simulate support.
    $batch = drush_op([$executable, 'prepareBatch']);
    drush_op('batch_set', $batch);
    $result = drush_op('drush_backend_batch_process');
    drush_op(function () {
      // XXX: Need to reset the batch status before setting and processing
      // another...
      $batch =& batch_get();
      $batch = [];
    });

    $executed_migrations += [$migration_id => $migration_id];
    if ($count = $executable->getFailedCount()) {
      $error_message = dt(
        '!name Migration - !count failed.',
        ['!name' => $migration_id, '!count' => $count]
      );
    }
    elseif (isset($result['drush_batch_process_finished']) && $result['drush_batch_process_finished'] !== TRUE) {
      $error_message = dt('!name migration failed.', ['!name' => $migration_id]);
    }
    else {
      $error_message = '';
    }

    if ($error_message) {
      if ($options['continue-on-failure']) {
        $this->logger()->error($error_message);
      }
      else {
        // Nudge Drush to use a non-zero exit code.
        throw new \Exception($error_message);
      }
    }
  }

  /**
   * Rollback one or more migrations.
   *
   * XXX: Largely copypasta from
   * \Drupal\migrate_tools\Commands\MigrateToolsCommands::rollback() with the
   * exception of: The "statuses" option, and use of our MigrateBatchExecutable
   * class to handle it.
   *
   * @param string $migration_names
   *   Name of migration(s) to rollback. Delimit multiple using commas.
   * @param array $options
   *   Additional options for the command.
   *
   * @command dgi-migrate:rollback
   *
   * @option all Process all migrations.
   * @option group A comma-separated list of migration groups to rollback
   * @option tag ID of the migration tag to rollback
   * @option feedback Frequency of progress messages, in items processed
   * @option idlist Comma-separated list of IDs to rollback
   * @option idlist-delimiter The delimiter for records
   * @option skip-progress-bar Skip displaying a progress bar.
   * @option continue-on-failure When a rollback fails, continue processing
   *   remaining migrations.
   * @option statuses
   *   An optional set of row statuses, comma-separated, to which to constrain
   *   the rollback. Valid states are: "imported", "needs_update", "ignored",
   *   and "failed".
   *
   * @default $options []
   *
   * @usage dgi-migrate:rollback --all
   *   Perform all migrations
   * @usage dgi-migrate:rollback --group=beer
   *   Rollback all migrations in the beer group
   * @usage dgi-migrate:rollback --tag=user
   *   Rollback all migrations with the user tag
   * @usage dgi-migrate:rollback --group=beer --tag=user
   *   Rollback all migrations in the beer group and with the user tag
   * @usage dgi-migrate:rollback beer_term,beer_node
   *   Rollback imported terms and nodes
   * @usage dgi-migrate:rollback beer_user --idlist=5
   *   Rollback imported user record with source ID 5
   * @validate-module-enabled migrate_tools
   *
   * @throws \Exception
   *   If there are not enough parameters to the command.
   */
  public function rollback($migration_names = '', array $options = [
    'all' => FALSE,
    'group' => self::REQ,
    'tag' => self::REQ,
    'feedback' => self::REQ,
    'idlist' => self::REQ,
    'idlist-delimiter' => self::DEFAULT_ID_LIST_DELIMITER,
    'skip-progress-bar' => FALSE,
    'continue-on-failure' => FALSE,
    'statuses' => self::REQ,
  ]) {
    $group_names = $options['group'];
    $tag_names = $options['tag'];
    $all = $options['all'];
    if (!$all && !$group_names && !$migration_names && !$tag_names) {
      throw new \Exception(dt('You must specify --all, --group, --tag, or one or more migration names separated by commas'));
    }

    $migrations = $this->migrationsList($migration_names, $options);
    if (empty($migrations)) {
      $this->logger()->error(dt('No migrations found.'));
    }

    // Take it one group at a time,
    // rolling back the migrations within each group.
    $has_failure = FALSE;
    foreach ($migrations as $migration_list) {
      // Roll back in reverse order.
      $migration_list = array_reverse($migration_list);
      foreach ($migration_list as $migration_id => $migration) {
        if ($options['skip-progress-bar']) {
          $migration->set('skipProgressBar', TRUE);
        }
        // Initialize the Synmfony Console progress bar.
        // XXX: Copypasta, don't care about the global use.
        // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
        \Drupal::service('migrate_tools.migration_drush_command_progress')->initializeProgress(
          $this->output(),
          $migration
        );
        $executable = new MigrateBatchExecutable(
          $migration,
          $this->getMigrateMessage(),
          $options
        );
        // drush_op() provides --simulate support.
        $result = drush_op([$executable, 'rollback']);
        if ($result == MigrationInterface::RESULT_FAILED) {
          $has_failure = TRUE;
        }
      }
    }

    // If any rollbacks failed, throw an exception to generate exit status.
    if ($has_failure) {
      $error_message = dt('!name migration failed.', ['!name' => $migration_id]);
      if ($options['continue-on-failure']) {
        $this->logger()->error($error_message);
      }
      else {
        // Nudge Drush to use a non-zero exit code.
        throw new \Exception($error_message);
      }
    }
  }

}
