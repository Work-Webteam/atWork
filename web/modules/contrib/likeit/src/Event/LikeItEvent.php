<?php

namespace Drupal\likeit\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a like/unlike event for event subscribers.
 */
class LikeItEvent extends Event {

  /**
   * The target entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs an like/unlike event object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The target entity.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Get the target entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

}
