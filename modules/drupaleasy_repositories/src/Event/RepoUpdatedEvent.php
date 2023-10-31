<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Event that is fired when a repository node is added, updated, or deleted.
 */
class RepoUpdatedEvent extends Event {

  /**
   * The name of the event triggered when a repository is updated.
   *
   * @Event
   *
   * @var string
   */
  const REPO_UPDATED = 'drupaleasy_repositories_repo_updated';

  /**
   * The repository node that was added/edited/deleted.
   *
   * @var \Drupal\node\NodeInterface
   */
  public NodeInterface $node;

  /**
   * The action that was performed on the $node.
   *
   * This action will be one of the following values: 'created', 'updated', or
   * 'deleted'.
   *
   * @var string
   */
  public string $action;

  /**
   * Constructs the event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that was added/updated/deleted.
   * @param string $action
   *   The action that was performed on the $node.
   */
  public function __construct(NodeInterface $node, string $action) {
    $this->node = $node;
    $this->action = $action;
  }

}
