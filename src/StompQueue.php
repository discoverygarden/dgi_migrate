<?php

namespace Drupal\dgi_migrate;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Queue\QueueInterface;
use Stomp\States\IStateful;
use Stomp\Transport\Message;

class StompQueue implements QueueInterface {

  use DependencySerializationTrait {
    __sleep as dstSleep;
  }

  protected IStateful $stomp;

  protected string $name;
  protected string $group;

  protected bool $subscribed = FALSE;

  protected int $serial = 0;


  public function __construct(
    IStateful $stomp,
    string $name,
    string $group
  ) {
    $this->stomp = $stomp;
    $this->name = $name;
    $this->group = $group;
  }

  public static function create(string $name, string $group) {
    return new static(
      \Drupal::service('islandora.stomp'),
      $name,
      $group
    );
  }

  /**
   * @inheritDoc
   */
  public function createItem($data) {
    $id = $this->serial++;

    $body = new \stdClass();
    $body->data = $data;
    $body->item_id = $id;
    $body->created = time();

    $message = new Message(
      serialize($body),
      [
        'dgi_migrate_migration' => $this->name,
        'dgi_migrate_run_id' => $this->group,
        'persistent' => 'true',
      ]
    );

    $this->stomp->send(
      '/queue/dgi_migrate',
      $message
    );

    return $id;
  }

  /**
   * @inheritDoc
   */
  public function numberOfItems() {
    return $this->id;
  }

  protected function subscribe() {
    if (!$this->subscribed) {
      $this->stomp->subscribe(
        "/queue/dgi_migrate",
        "dgi_migrate_migration = '{$this->name}' AND dgi_migrate_run_id = '{$this->group}'"
      );
      $connection = $this->stomp->getClient()->getConnection();
      $connection->setReadTimeout(10);

      if (extension_loaded('pcntl')) {
        pcntl_signal(SIGUSR1, [$this, 'pcntlSignalHandler']);
        pcntl_signal(SIGINT, [$this, 'pcntlSignalHandler']);
        $connection->setWaitCallback([$this, 'pcntlWaitCallback']);
      }
      $this->subscribed = TRUE;
    }
  }

  /**
   * Signal flag.
   *
   * @var bool
   *
   * @see https://github.com/stomp-php/stomp-php-examples/blob/693d436228c49eabeda853d1c390dab0ce0ace7d/src/pcntl_signal_handling.php#L20-L21
   */
  protected bool $signalled = FALSE;

  /**
   * Signal handler.
   *
   * @return void
   *
   * @see https://github.com/stomp-php/stomp-php-examples/blob/693d436228c49eabeda853d1c390dab0ce0ace7d/src/pcntl_signal_handling.php#L26-L29
   */
  public function pcntlSignalHandler() {
    $this->signalled = TRUE;
  }

  /**
   * Wait callback.
   *
   * @return false|void
   *
   * @see https://github.com/stomp-php/stomp-php-examples/blob/693d436228c49eabeda853d1c390dab0ce0ace7d/src/pcntl_signal_handling.php#L38-L53
   */
  public function pcntlWaitCallback() {
    pcntl_signal_dispatch();
    if ($this->signalled) {
      return FALSE;
    }
  }

  /**
   * @inheritDoc
   */
  public function claimItem($lease_time = 3600) {
    $this->subscribe();

    $frame = $this->stomp->read();

    if ($frame === FALSE) {
      return FALSE;
    }

    $to_return = unserialize($frame->getBody());
    $to_return->frame = $frame;
    return $to_return;
  }

  /**
   * @inheritDoc
   */
  public function deleteItem($item) {
    $this->stomp->ack($item->frame);
  }

  /**
   * @inheritDoc
   */
  public function releaseItem($item) {
    $this->stomp->nack($item->frame);
  }

  /**
   * @inheritDoc
   */
  public function createQueue() {
    // No-op.
  }

  /**
   * @inheritDoc
   */
  public function deleteQueue() {
    // No-op... can't delete via STOMP.
  }

  public function __sleep() {
    $vars = $this->dstSleep();

    $to_suppress = [
      // XXX: Avoid serializing some things that we don't need.
      'subscribed',
      'signalled',
    ];
    foreach ($to_suppress as $value) {
      $key = array_search($value, $vars);
      if ($key !== FALSE) {
        unset($vars[$key]);
      }
    }

    return $vars;
  }

}
