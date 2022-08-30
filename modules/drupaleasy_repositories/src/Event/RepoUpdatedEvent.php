<?php

namespace Drupal\drupaleasy_repositories\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\Entity\Node;

/**
 * Event that is fired when a repository is create/updated/deleted.
 */
class RepoUpdatedEvent extends Event {

  const EVENT_NAME = 'drupaleasy_repositories_repo_updated';

  /**
   * The node updated.
   *
   * @var \Drupal\node\Entity\Node
   */
  public $node;

  /**
   * The action performed on the node.
   *
   * @var string
   */
  public $action;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node that was created/updated/deleted.
   * @param string $action
   *   The action performed on the node.
   */
  public function __construct(Node $node, string $action) {
    $this->node = $node;
    $this->action = $action;
  }

}
