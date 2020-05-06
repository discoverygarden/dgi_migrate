<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

/**
 * Element handler for foxml:binaryContent.
 */
class BinaryContent extends AbstractStreamOffsetContent {

  use LeafTrait;

  const TAG = 'foxml:binaryContent';

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    $uri = parent::getUri();
    return "php://filter/read=convert.base64-decode/resource={$uri}";
  }

}
