<?php

namespace Drupal\dgi_migrate;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider.
 */
class DgiMigrateServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('migrate.stub')) {
      $container->getDefinition('migrate.stub')
        ->setClass(MigrateStub::class);
    }
  }

}
