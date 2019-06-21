<?php

namespace Drupal\forward\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event that is fired when an entity is forwarded.
 */
class EntityForwardEvent extends GenericEvent {

  const EVENT_NAME = 'forward_entity_forward';

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  public $entity;

  /**
   * The arguments.
   *
   * @var array
   */
  public $arguments;

  /**
   * Constructs the object.
   */
  public function __construct(UserInterface $account, EntityInterface $entity, array $arguments) {
    $this->account = $account;
    $this->entity = $entity;
    $this->arguments = $arguments;
  }

}
