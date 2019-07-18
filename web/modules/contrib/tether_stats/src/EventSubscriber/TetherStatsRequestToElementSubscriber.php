<?php

namespace Drupal\tether_stats\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\tether_stats\TetherStatsEvents;
use Drupal\tether_stats\Event\TetherStatsRequestToElementEvent;
use Drupal\tether_stats\TetherStatsIdentitySet;

/**
 * Service that subscribes to the request to element stats event.
 *
 * The TetherStatsRequestSubscriber will dispatch an event that will allow
 * subscribers, like this one, to define identity sets for an http request.
 * The identity set suggested will change what kind of stats element is
 * associated with this particular request.
 *
 * The default behavior for the TetherStatsRequestSubscriber is just to map a
 * stats element to the request URL. This resolves to a database entry in the
 * tether_stats_element table where only the URL field is specified. This
 * service, however, will see if the request if for a node page. If it is, it
 * will provide a better, entity bound, TetherStatsIdentitySet to use instead.
 *
 * In this way, node pages will be stored in the table with entity_id and
 * entity_type fields instead of just the URL making it easier data mine.
 *
 * There is no enforcement on what pages get mapped with what identity sets.
 * You can, for example, have node pages bound to stats elements for user
 * entities. This gives a great amount of flexibility, but some thought
 * should be taken when creating other TetherStatsEvents::REQUEST_TO_ELEMENT
 * subscribers.
 */
class TetherStatsRequestToElementSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[TetherStatsEvents::REQUEST_TO_ELEMENT] = 'onRequestToElement';
    return $events;
  }

  /**
   * This handler will bind node pages to respective stat elements.
   *
   * @param TetherStatsRequestToElementEvent $event
   *   The request to element event.
   */
  public function onRequestToElement(TetherStatsRequestToElementEvent $event) {

    $route_match = $event->getRouteMatch();

    switch ($route_match->getRouteName()) {

      case 'entity.node.canonical':

        $config = \Drupal::config('tether_stats.settings');

        $identity_parameters = [
          'entity_id' => $route_match->getRawParameters()->get('node'),
          'entity_type' => 'node',
          'url' => parse_url($event->getRequestUri(), PHP_URL_PATH),
        ];

        if ($config->get('allow_query_string_elements')) {

          $query = parse_url($event->getRequestUri(), PHP_URL_QUERY);

          if (!empty($query)) {

            $identity_parameters['query'] = $query;
          }
        }

        // Set the identity set and stop the event propagation.
        $event->setIdentitySet(new TetherStatsIdentitySet($identity_parameters));
        break;
    }
  }

}
