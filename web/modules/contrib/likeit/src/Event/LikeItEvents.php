<?php

namespace Drupal\likeit\Event;

/**
 * Defines like/unlike events for the likeit module.
 */
final class LikeItEvents {

  /**
   * Name of the event fired when a entity is liked.
   *
   * This event allows modules to perform an action whenever a entity is
   * liked by user. The event listener method receives a
   * \Drupal\likeit\Event\LikeItEvent instance.
   *
   * @Event
   *
   * @see \Drupal\likeit\Event\LikeItEvent
   *
   * @var string
   */
  const LIKE = 'likeit.like';

  /**
   * Name of the event fired when a entity is unliked.
   *
   * This event allows modules to perform an action whenever a entity is
   * unliked by user. The event listener method receives a
   * \Drupal\likeit\Event\LikeItEvent instance.
   *
   * @Event
   *
   * @see \Drupal\likeit\Event\LikeItEvent
   *
   * @var string
   */
  const UNLIKE = 'likeit.unlike';

}
