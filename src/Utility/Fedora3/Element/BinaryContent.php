<?php

namespace Drupal\dgi_migrate\Utility\Fedora3\Element;

use Drupal\dgi_migrate\Utility\Fedora3\AbstractParser;

class BinaryContent extends AbstractStreamOffsetContent {

  use LeafTrait;

  const TAG = 'foxml:binaryContent';

  //public function getUri() {
  //  $uri = parent::getUri();
  //  return "php://filter/read=convert.base64-decode/resource={$uri}";
  //}
}
