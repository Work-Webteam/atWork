<?php

namespace Drupal\tether_stats;

/**
 * Contains all events thrown for tether stats.
 */
final class TetherStatsEvents {

  /**
   * Name of the event fired when a request is being mapped to an element.
   *
   * This event is used to modify how a request gets mapped to a stats
   * element. Modules are given the opportunity to specify their own
   * element values instead of the default behavior.
   *
   * @Event
   *
   * @see \Drupal\tether_stats\EventSubscriber\TetherStatsRequestSubscriber
   *
   * @var string
   */
  const REQUEST_TO_ELEMENT = 'tether_stats.request_to_element';

}
