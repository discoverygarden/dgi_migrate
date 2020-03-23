<?php

namespace Drupal\dgi_migrate\Utility;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

class Fedora3Sparql implements \Iterator, \Countable {
  const METHOD = 'GET';
  protected $client;
  protected $countResponse;
  protected $queryResponse;

  public function __construct(ClientInterface $client, $url, $user, $pass) {
    $this->client = $client;

    $this->url = $url;
    $this->user = $user;
    $this->pass = $pass;

  }

  protected function initialize() {
    $this->countResponse = $this->client->sendAsync($this->buildCountRequest());
    $this->queryResponse = $this->client->sendAsync($this->buildQueryRequest());
  }

  protected function buildCountRequest() {
    $request = new Request(
      static::METHOD,
      $this->url,
      [
        'Authorization' => implode(' ', [
          'Basic',
          base64_encode(implode(':', [
            $this->user,
            $this->pass,
          ]),
        ]),
      ]
    );

    $uri = $request->getUri();


    return $request;
  }

  protected function buildQueryRequest() {}

  protected function orderedQuery($filter = '') {
    $order_statement = <<<EOQ
ORDER BY ASC(?date)
EOQ;
    return implode("\n", array_filter([
      $this->baseQuery(),
      $filter,
      $order_statement,
    ]));
  }

  protected function baseQuery() {
    return <<<EOQ
PREFIX fedora-model: <info:fedora/fedora-system:def/model#>
SELECT ?pid ?date
FROM <#ri>
WHERE {
  ?pid fedora-model:hasModel <info:fedora/fedora-system:FedoraObject-3.0> ;
       fedora-model:createdDate ?date .
}
EOQ;
  }

  public function count() {
    // TODO: Prepare and execute count query... memoize?
  }
}
