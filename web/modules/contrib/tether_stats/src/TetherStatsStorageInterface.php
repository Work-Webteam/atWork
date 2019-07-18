<?php

namespace Drupal\tether_stats;

/**
 * An interface for TetherStatsStorage.
 */
interface TetherStatsStorageInterface {

  /**
   * Load a stats element by its id.
   *
   * @param int $elid
   *   The id of the element to load.
   *
   * @return TetherStatsElementInterface|null
   *   The element object or null if no element was found.
   */
  public function loadElement($elid);

  /**
   * Load a stats element by its identity set.
   *
   * @param TetherStatsIdentitySetInterface $identity_set
   *   The collection of parameters which uniquely identify an element.
   *
   * @return TetherStatsElementInterface|null
   *   The element object or null if no matching element was found.
   */
  public function loadElementFromIdentitySet(TetherStatsIdentitySetInterface $identity_set);

  /**
   * Construct a TetherStatsElement object from a given identity set.
   *
   * Loads the stats element data from the database for the element that is
   * identified by the $identity_set. If no element currently exists then
   * an entry will be added to the tether_stats_element table.
   *
   * Element data will also be updated with values from the $identity_set if
   * the element's time to live has expired.
   *
   * The $identity_set will be tested for validity using the isValid() method
   * and, as such, may throw various exceptions.
   *
   * @param TetherStatsIdentitySetInterface $identity_set
   *   The set of parameters which uniquely identifies a stats element.
   *
   * @return TetherStatsElementInterface|null
   *   The TetherStatsElement object or null if the $identity_set is invalid.
   *
   * @see \Drupal\tether_stats\TetherStatsIdentitySetInterface::isValid()
   */
  public function createElementFromIdentitySet(TetherStatsIdentitySetInterface $identity_set);

  /**
   * Retrieves a count of how many stat elements are from a derivative.
   *
   * @param string $derivative
   *   The unique string ID of the derivative.
   *
   * @return int
   *   The derivative count.
   */
  public function getDerivativeUsageCount($derivative);

  /**
   * Add an entry to the activity log for an event.
   *
   * Adds an entry to the tether_stats_activity_log table.
   *
   * @param int $elid
   *   The id of the element which sourced the event.
   * @param string $event_type
   *   The type of event such as a 'hit' or 'click'.
   * @param int $event_time
   *   The time in which the event occurred.
   * @param string $ip_address
   *   The ip address of the user.
   * @param string $session_id
   *   The session id of the user.
   * @param string $browser
   *   The browser description string usually from $_SERVER['HTTP_USER_AGENT'].
   * @param string $referrer
   *   (Optional) The string describing the referrer or the page the user was
   *   on before this event.
   * @param int $uid
   *   (Optional) The id of the user which created the event if the user was
   *   logged in.
   *
   * @return int
   *   The activity log id.
   */
  public function trackActivity($elid, $event_type, $event_time, $ip_address, $session_id, $browser, $referrer = NULL, $uid = NULL);

  /**
   * Add an entry to the impression log.
   *
   * Adds an entry to the tether_stats_impression_log table to
   * record that the element $elid was impressed during the
   * activity $alid.
   *
   * @param int $elid
   *   The element that was impressed.
   * @param int $alid
   *   The activity where the element was impressed.
   * @param int $event_time
   *   The time in which the event occurred.
   *
   * @return int
   *   The impression log id.
   */
  public function trackImpression($elid, $alid, $event_time);

}
