<?php

namespace Drupal\tether_stats;

/**
 * Interface for a TetherStatsRequestFilter.
 *
 * A request filter is responsible for taking a request and determine if
 * that request should be filtered from stats collection based on filter
 * rules defined in the module configuration.
 */
interface TetherStatsRequestFilterInterface {

  /**
   * The exclude filter mode.
   *
   * This mode will cause requests matching any of the filter rules to be
   * filtered.
   *
   * @var string
   */
  const MODE_EXCLUDE = 'exclude';

  /**
   * The include filter mode.
   *
   * This mode will cause requests matching any of the filter rules to be
   * accepted and not filtered.
   *
   * @var string
   */
  const MODE_INCLUDE = 'include';

  /**
   * Determines if a URL is filtered.
   *
   * @param string $url
   *   The request URL.
   *
   * @return bool
   *   Return TRUE if the request has been filtered.
   */
  public function isUrlFiltered($url);

  /**
   * Determines if the route name is filtered by the $route_filter array.
   *
   * @param string $route_name
   *   The name of the route to filter.
   *
   * @return bool
   *   Return TRUE if the route has been filtered.
   */
  public function isRouteFiltered($route_name);

  /**
   * Determines if a url matches a given rule.
   *
   * @param array $url_parts
   *   The url broken into its '/' dilineated parts.
   * @param array $rule_parts
   *   A url rule broken into its '/' dilineated parts.
   *
   * @return bool
   *   Returns TRUE if the url matches the given rule.
   */
  public static function doesUrlMatchRule(array $url_parts, array $rule_parts);

  /**
   * Determines if a route name matches a given rule.
   *
   * @param array $route_parts
   *   The route name broken into its '.' dilineated parts.
   * @param array $rule_parts
   *   A url rule broken into its '.' dilineated parts.
   *
   * @return bool
   *   Returns TRUE if the route matches the given rule.
   */
  public static function doesRouteMatchRule(array $route_parts, array $rule_parts);

}
