<?php

namespace Drupal\tether_stats\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\tether_stats\TetherStatsIdentitySetInterface;

/**
 * Represents an event to map a request to a stats element.
 */
class TetherStatsRequestToElementEvent extends Event {

  /**
   * The identity set that uniquely defines an element.
   *
   * Once set through setIdentitySet(), this event stops propagation.
   *
   * @var \Drupal\tether_stats\TetherStatsIdentitySetInterface
   */
  protected $identitySet;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request URI.
   *
   *  May contain a query string.
   *
   * @var string
   */
  protected $requestUri;

  /**
   * Constructor for TetherStatsElementEvent.
   *
   * @param RouteMatchInterface $route_match
   *   The current route match.
   * @param string $request_uri
   *   The master request URI.
   */
  public function __construct(RouteMatchInterface $route_match, $request_uri) {

    $this->routeMatch = $route_match;
    $this->requestUri = $request_uri;
  }

  /**
   * Determines if the identity set has been set.
   *
   * @return bool
   *   Returns TRUE if the $identitySet property has been set.
   */
  public function hasIdentityset() {

    return $this->identitySet;
  }

  /**
   * Gets the identity set.
   *
   * @return TetherStatsIdentitySetInterface|null
   *   The TetherStatsIdentitySet object or null if it has not been set.
   */
  public function getIdentityset() {

    return $this->identitySet;
  }

  /**
   * Gets the request Uri.
   *
   * @return string
   *   The request Uri.
   */
  public function getRequestUri() {

    return $this->requestUri;
  }

  /**
   * Gets the current route match.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The route match inferface.
   */
  public function getRouteMatch() {

    return $this->routeMatch;
  }

  /**
   * Set the identity set for the element this request should map to.
   *
   * Once set, this event stops propagation.
   *
   * The identity set will not be here be validated here but will be before
   * a stats element is created from it. If the validity of the set is
   * uncertain, TetherStatsIdentitySet::isValid() should be executed and
   * any exceptions handled.
   *
   * @param \Drupal\tether_stats\TetherStatsIdentitySetInterface $identity_set
   *   The identity set to provide the TetherStatsRequestSubscriber for
   *   assigning a stats element to the request.
   *
   * @see TetherStatsIdentitySetInterface::isValid()
   */
  public function setIdentitySet(TetherStatsIdentitySetInterface $identity_set) {

    $this->identitySet = $identity_set;
    $this->stopPropagation();
  }

}
