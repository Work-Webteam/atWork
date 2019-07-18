<?php

namespace Drupal\tether_stats;

/**
 * Provides a stat collection filter for requests.
 *
 * A request filter is responsible for taking a request and determining if
 * that request should be filtered from stats collection based on defined
 * filter rules.
 *
 * There are two types of filters provided. A route name filter, and a
 * request url filter.
 *
 * A - Route filter.
 *
 * The isRouteFiltered() method will determine if a given route name is
 * filtered by the rules set out in $routeFilterRules.
 *
 * A rule in $routeFilterRules can be a name of a specific route, or a
 * partial route name with wildcard * characters in place of route parts.
 *
 * - entity.node.canonical: Matches all node view pages.
 * - entity.*.canonical: Matches all entity view pages.
 * - tether_stats.*: Matches all Tether Stats pages.
 *
 * B - Url Filter
 *
 * The isUrlFiltered() method will detemine if a given url string is
 * filtered by the rules set out in $urlFilterRules.
 *
 * A rule in $routeFilterRules can be a specific url, or a url with
 * wildcard % and # characters in place of url parts. The % character
 * will match anything whereas the # character will only match numbers.
 * A matching URL will also match any other URL which extends it.
 *
 * - admin: Matches all pages that begin with "/admin"
 * - node/1/edit: Matches the edit page for node with nid 1.
 * - node/#/edit: Matches all node edit pages.
 * - %/track: Matches "/tether_stats/track".
 * - #/track: Will not match anything.
 */
class TetherStatsRequestFilter implements TetherStatsRequestFilterInterface {

  /**
   * The array of route filter rules.
   *
   * @var string
   */
  protected $mode;

  /**
   * The array of route filter rules.
   *
   * @var array
   */
  protected $routeFilterRules;

  /**
   * The array of url filter rules.
   *
   * @var array
   */
  protected $urlFilterRules;

  /**
   * Constructs a TetherStatsRequestFilter.
   *
   * @param array $route_filter_rules
   *   An array of route filter rules to apply.
   * @param array $url_filter_rules
   *   An array of url filter rules to apply.
   * @param string $mode
   *   The exclusion mode. Can be either 'include' or 'exclude' or one of
   *   TetherStatsRequestFilterInterface::MODE_INCLUDE,
   *   TetherStatsRequestFilterInterface::MODE_EXCLUDE.
   */
  public function __construct(array $route_filter_rules, array $url_filter_rules, $mode = TetherStatsRequestFilterInterface::MODE_EXCLUDE) {

    $this->mode = $mode;
    $this->routeFilterRules = $route_filter_rules;
    $this->urlFilterRules = $url_filter_rules;
  }


  /**
   * {@inheritdoc}
   */
  public function isUrlFiltered($url) {

    $does_match_rule = FALSE;

    $path = trim(parse_url($url, PHP_URL_PATH), '/');
    $url_parts = explode('/', $path);

    foreach ($this->urlFilterRules as $filter_path) {

      $rule_parts = explode('/', trim($filter_path, '/'));

      if (TetherStatsRequestFilter::doesUrlMatchRule($url_parts, $rule_parts)) {

        $does_match_rule = TRUE;
        break;
      }
    }

    switch ($this->mode) {

      case TetherStatsRequestFilterInterface::MODE_INCLUDE:
        $is_filtered = !$does_match_rule;
        break;

      case TetherStatsRequestFilterInterface::MODE_EXCLUDE:
      default:
        $is_filtered = $does_match_rule;
        break;

    }

    return $is_filtered;
  }

  /**
   * {@inheritdoc}
   */
  public function isRouteFiltered($route_name) {

    $does_match_rule = FALSE;

    $route_parts = explode('.', $route_name);

    foreach ($this->routeFilterRules as $route_filter_rule) {

      $rule_parts = explode('.', $route_filter_rule);

      if (TetherStatsRequestFilter::doesRouteMatchRule($route_parts, $rule_parts)) {

        $does_match_rule = TRUE;
        break;
      }
    }

    switch ($this->mode) {

      case TetherStatsRequestFilterInterface::MODE_INCLUDE:
        $is_filtered = !$does_match_rule;
        break;

      case TetherStatsRequestFilterInterface::MODE_EXCLUDE:
      default:
        $is_filtered = $does_match_rule;
        break;

    }

    return $is_filtered;
  }

  /**
   * {@inheritdoc}
   */
  public static function doesUrlMatchRule(array $url_parts, array $rule_parts) {

    $rule_match = FALSE;

    // If the rule is longer than the url, we automatically fail to match.
    if (!empty($rule_parts) && count($url_parts) >= count($rule_parts)) {

      for ($i = 0; $i < count($rule_parts); $i++) {

        switch ($rule_parts[$i]) {

          case '%':
            $rule_match = TRUE;
            break;

          case '#':
            $rule_match = is_numeric($url_parts[$i]);
            break;

          default:
            $rule_match = (strcasecmp($url_parts[$i], $rule_parts[$i]) == 0);
            break;

        }

        if (!$rule_match) {

          break;
        }
      }
    }

    return $rule_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function doesRouteMatchRule(array $route_parts, array $rule_parts) {

    $rule_match = FALSE;

    // If the rule is longer than the route, we automatically fail to match.
    if (!empty($rule_parts) && count($route_parts) >= count($rule_parts)) {

      for ($i = 0; $i < count($rule_parts); $i++) {

        switch ($rule_parts[$i]) {

          case '*':
            $rule_match = TRUE;
            break;

          default:
            $rule_match = (strcasecmp($route_parts[$i], $rule_parts[$i]) == 0);
            break;

        }

        if (!$rule_match) {

          break;
        }
      }
    }

    if ($rule_match) {

      if (count($route_parts) > count($rule_parts)) {

        // If there are more parts to the route than specified in the rule
        // a successful match only occurs if the last rule part is the '*'
        // wildcard.
        $rule_match = ($rule_parts[count($rule_parts) - 1] == '*');
      }
    }

    return $rule_match;
  }

}
