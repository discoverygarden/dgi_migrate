<?php

namespace Drupal\dgi_migrate\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

use Drupal\dgi_migrate\Form\MigrationExecuteForm;

/**
 * Route subscriber handler.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('migrate_tools.execute')) {
      $route->setDefault('_form', MigrationExecuteForm::class);
    }
  }

}
