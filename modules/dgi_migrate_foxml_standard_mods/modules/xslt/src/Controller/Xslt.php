<?php

namespace Drupal\dgi_migrate_foxml_standard_mods_xslt\Controller;

use Drupal\dgi_migrate_foxml_standard_mods_xslt\Form\Settings;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Config\Config;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serve our XSLT from config.
 */
class Xslt implements ContainerInjectionInterface {

  /**
   * Our config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructor.
   */
  public function __construct(Config $config, Request $current_request) {
    $this->config = $config;
    $this->currentRequest = $current_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get(Settings::CONFIG),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Controller content callback.
   */
  public function content() {
    $xslt = $this->config->get('xslt');
    if (!$xslt) {
      throw new NotFoundHttpException('The XSLT has not been set.');
    }

    return (new CacheableResponse($xslt, 200, [
      'Content-Type' => 'application/xslt+xml',
    ]))
      ->addCacheableDependency($this->config);
  }

  /**
   * Helper; determine the client's IP address.
   *
   * @return string
   *   The IP address.
   */
  protected function getRemoteIp() {
    return $this->currentRequest->getClientIp();
  }

  /**
   * Access callback.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf(
      IpUtils::checkIp(
        $this->getRemoteIp(),
        $this->config->get('allowed_remotes')
      )
    )
      ->addCacheableDependency($this->config)
      ->addCacheContexts(['ip']);
  }

}
