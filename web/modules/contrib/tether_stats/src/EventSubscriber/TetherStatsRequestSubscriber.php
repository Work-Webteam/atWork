<?php

namespace Drupal\tether_stats\EventSubscriber;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\tether_stats\TetherStatsEvents;
use Drupal\tether_stats\TetherStatsElement;
use Drupal\tether_stats\TetherStatsIdentitySet;
use Drupal\tether_stats\Event\TetherStatsRequestToElementEvent;
use Drupal\tether_stats\TetherStatsManagerInterface;
use Drupal\tether_stats\TetherStatsRequestFilter;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Service which subcribes to the request event.
 *
 * The job of this service is to map a request to a stats element. The
 * TetherStatsManagerInterface::setElement() method will be called once
 * complete so stat tracking can later reference the element for the
 * current request.
 *
 * A TetherStatsRequestToElementEvent will be dispatched to allow
 * listeners the opportunity to suggest a TetherStatsIdentitySet which
 * will map to an individual stats element.
 *
 * Calling TetherStatsRequestToElementEvent::setIdentitySet() with a
 * valid TetherStatsIdentitySet will stop the event propagation and
 * cause the TetherStatsElement created from the identity set to
 * become the representative of this request or page.
 */
class TetherStatsRequestSubscriber implements EventSubscriberInterface {

  /**
   * The Tether Stats manager service.
   *
   * @var \Drupal\tether_stats\TetherStatsManagerInterface
   */
  protected $manager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructs a TetherStatsRequestSubscriber.
   *
   * @param \Drupal\tether_stats\TetherStatsManagerInterface $manager
   *   The Tether Stats manager service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher service.
   */
  public function __construct(TetherStatsManagerInterface $manager, CurrentRouteMatch $route_match, EventDispatcherInterface $dispatcher) {

    $this->manager = $manager;
    $this->routeMatch = $route_match;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST] = 'onRequest';
    return $events;
  }

  /**
   * Event listener for the KernelEvents::REQUEST event.
   *
   * Parses the URI and route information to associate the current request with
   * a unique stats element.
   *
   * Once an element has been matched to the request, a TetherStatsElement
   * object will be created and add to the TetherStatsManager service. This
   * allows event activity reported later through AJAX, to reference the
   * page which spawned them.
   *
   * This method will dispatch another event, namely TetherStatsEvents::ELEMENT,
   * that will allow other listeners to contribute an identity set to bind
   * the current request to a stats element.
   *
   * No response will be added to the GetResponseEvent.
   *
   * @param GetResponseEvent $event
   *   The get response event.
   */
  public function onRequest(GetResponseEvent $event) {

    $config = $this->manager->getSettings();
    $request_uri = $event->getRequest()->getRequestUri();

    // Only process the request if stats collection is active.
    if ($this->manager->isActive()) {

      // Do not construct an element for the current request if it has been
      // filtered by the configuration settings. This prevents stats from being
      // collected from certain pages.
      if (!$this->isFiltered($request_uri, $config)) {

        // Dispatch an event which will allow listeners to provide an identity
        // set for the current request.
        $element_event = new TetherStatsRequestToElementEvent($this->routeMatch, $request_uri);

        $this->dispatcher->dispatch(TetherStatsEvents::REQUEST_TO_ELEMENT, $element_event);

        if ($element_event->hasIdentityset()) {

          $identity_set = $element_event->getIdentityset();
        }
        else {

          // If no listener has provided an identity set to map an element to
          // this request, then create a default identity set based solely on
          // the URL.
          $identity_parameters = [
            'url' => parse_url($request_uri, PHP_URL_PATH),
          ];

          if ($config->get('allow_query_string_elements')) {

            $query = parse_url($request_uri, PHP_URL_QUERY);

            if (!empty($query)) {

              $identity_parameters['query'] = $query;
            }
          }

          // The identity set is a simple url with query, so it should pass
          // validation.
          $identity_set = new TetherStatsIdentitySet($identity_parameters);
        }

        // Make sure the identity set is valid and log any issues if it is not.
        if ($this->manager->testValidityOfIdentitySet($identity_set)) {

          // Construct an element from the identity set. This creates an entry
          // in the database.
          $element = TetherStatsElement::createElementFromIdentitySet($identity_set);

          // Set the element on the manager so all other processes have access
          // to it.
          $this->manager->setElement($element);
        }
      }
    }
  }

  /**
   * Determines if the current request is filtered.
   *
   * @param string $request_uri
   *   The request URI string.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The Tether Stats configuration settings.
   *
   * @return bool
   *   Return TRUE if the current request is filtered.
   */
  public function isFiltered($request_uri, ImmutableConfig $config) {

    $route_filter_rules = $config->get('filter.rules.route');
    $url_filter_rules = $config->get('filter.rules.url');

    // Add the tracking route by default.
    $route_filter_rules[] = 'tether_stats.track';

    $filter = new TetherStatsRequestFilter($route_filter_rules, $url_filter_rules, $config->get('filter.mode'));

    $is_filtered = $filter->isRouteFiltered($this->routeMatch->getRouteName());

    if (!$is_filtered) {

      $is_filtered = $filter->isUrlFiltered($request_uri);
    }

    return $is_filtered;
  }

}
