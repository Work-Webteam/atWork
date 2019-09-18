<?php

namespace Drupal\tether_stats;

use Drupal\tether_stats\TetherStatsIdentitySetInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;

/**
 * Interface for a TetherStatsManager.
 */
interface TetherStatsManagerInterface {

  /**
   * Gets the database storage object for activity tracking.
   *
   * @return TetherStatsStorageInterface
   *   The database storage.
   */
  public function getStorage();

  /**
   * Gets the database storage object for data mining and analytics.
   *
   * @return TetherStatsAnalyticsStorageInterface
   *   The database storage.
   */
  public function getAnalyticsStorage();

  /**
   * Gets the Tether Stats configuration settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration settings.
   */
  public function getSettings();

  /**
   * Gets the Tether Stats logger channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger channel.
   */
  public function getLogger();

  /**
   * Determines if stat collection is turned on.
   *
   * @return bool
   *   Returns TRUE if stat collection is turned on.
   */
  public function isActive();

  /**
   * Sets the TetherStatsElement object which represents the current request.
   *
   * @param TetherStatsElementInterface $element
   *   The TetherStatsElement object.
   */
  public function setElement(TetherStatsElementInterface $element);

  /**
   * Gets the TetherStatsElement object which represents the current request.
   *
   * @return TetherStatsElementInterface|null
   *   The TetherStatsElement object or null if it has not been set.
   */
  public function getElement();

  /**
   * Determines if the current request element object has been set.
   *
   * @return bool
   *   Returns TRUE if the TetherStatsElement object has been set.
   */
  public function hasElement();

  /**
   * Gets the chart renderer.
   *
   * @return \Drupal\tether_stats\TetherStatsChartRendererInterface
   *   The chart renderer object.
   */
  public function getChartRenderer();

  /**
   * Generates a link with added attributes for tracking clicks.
   *
   * The "click" event is recorded relative to the stats element defined by
   * the identity set.
   *
   * The identity set parameters will be added to the link as attributes with
   * the key "data-{$key}" where {$key} is the identity parameter key.
   *
   * The class "tether_stats-track-link" will also be added which will flag
   * the link for click tracking.
   *
   * @param string $text
   *   The link text for the anchor tag as a translated string or render array.
   *   Strings will be sanitized automatically. If you need to output HTML in
   *   the link text, use a render array or an already sanitized string such as
   *   the output of \Drupal\Component\Utility\Xss::filter() or
   *   \Drupal\Component\Utility\SafeMarkup::format().
   * @param \Drupal\Core\Url $url
   *   The URL object used for the link. Amongst its options, the following may
   *   be set to affect the generated link: - attributes: An associative array
   *   of HTML attributes to apply to the anchor tag. If element 'class' is
   *   included, it must be an array; 'title' must be a string; other elements
   *   are more flexible, as they just need to work as an argument for the
   *   constructor of the class
   *   Drupal\Core\Template\Attribute($options['attributes']).
   *   - language: An optional language object. If the path being linked to is
   *   internal to the site, $options['language'] is used to determine whether
   *   the link is "active", or pointing to the current page (the language as
   *   well as the path must match).
   *   - 'set_active_class': Whether this method should compare the
   *   $route_name, $parameters, language and query options to the current URL
   *   to determine whether the link is "active". Defaults to FALSE. If TRUE,
   *   an "active" class will be applied to the link. It is important to use
   *   this sparingly since it is usually unnecessary and requires extra
   *   processing.
   * @param TetherStatsIdentitySetInterface $identity_set
   *   The identity set uniquely defining the stats element relative to
   *   which the "click" event should be recorded. If this identity set is
   *   invalid, the tracking will fail.
   *
   * @return string
   *   An HTML string containing a link to the given route and parameters.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  public function generateLink($text, Url $url, TetherStatsIdentitySetInterface $identity_set);

  /**
   * Tests the validity of an identity set and logs any issues.
   *
   * @param TetherStatsIdentitySetInterface $identity_set
   *   The identity set to validate.
   *
   * @return bool
   *   Returns TRUE if the set is valid.
   */
  public function testValidityOfIdentitySet(TetherStatsIdentitySetInterface $identity_set);

}
