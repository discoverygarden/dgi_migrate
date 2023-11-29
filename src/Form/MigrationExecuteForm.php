<?php

namespace Drupal\dgi_migrate\Form;

use Drupal\Core\Form\FormStateInterface;

use Drupal\dgi_migrate\MigrateBatchExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\Form\MigrationExecuteForm as MigrationExecuteFormBase;

/**
 * Slightly extended migration execution form.
 */
class MigrationExecuteForm extends MigrationExecuteFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {

    $operation = $form_state->getValue('operation');

    if ($form_state->getValue('limit')) {
      $limit = $form_state->getValue('limit');
    }
    else {
      $limit = 0;
    }

    if ($form_state->getValue('update')) {
      $update = $form_state->getValue('update');
    }
    else {
      $update = 0;
    }
    if ($form_state->getValue('force')) {
      $force = $form_state->getValue('force');
    }
    else {
      $force = 0;
    }

    $migration = $this->getRouteMatch()->getParameter('migration');
    if ($migration) {
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration_plugin */
      $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
      $migrateMessage = new MigrateMessage();

      switch ($operation) {
        case 'import':

          $options = [
            'limit' => $limit,
            'update' => $update,
            'force' => $force,
          ];

          $executable = new MigrateBatchExecutable($migration_plugin, $migrateMessage, $options);
          batch_set($executable->prepareBatch());

          break;

        default:
          parent::submitForm($form, $form_state);

          break;

      }
    }
  }

}
