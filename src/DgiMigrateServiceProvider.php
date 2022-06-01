<?php

namespace Drupal\dgi_migrate;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider.
 */
class DgiMigrateServiceProvider extends ServiceProviderBase {
  const TIMEOUT = 120;

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('migrate.stub')) {
      $container->getDefinition('migrate.stub')
        ->setClass(MigrateStub::class);
    }
    if ($container->hasDefinition('imagemagick.exec_manager')) {
      $container->getDefinition('imagemagick.exec_manager')
        ->addMethodCall('setTimeout', [self::TIMEOUT]);
    }
  }

}
