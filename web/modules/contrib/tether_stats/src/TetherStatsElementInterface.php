<?php

namespace Drupal\tether_stats;

/**
 * Interface for a TetherStatsElement object.
 */
interface TetherStatsElementInterface {

  /**
   * Get the element ID.
   */
  public function getId();

  /**
   * Get the count for the total number of hit activities on this element.
   */
  public function getCount();

  /**
   * Get the time when the element was created.
   */
  public function getCreated();

  /**
   * Get the time when the element was last updated.
   *
   * Does not include increases to the count value.
   */
  public function getChanged();

  /**
   * Get the time activity occurred on the element.
   *
   * That is, the last time the count was incremented.
   */
  public function getLastActivity();

  /**
   * Determines if an identity parameter has been set.
   *
   * @param string $key
   *   The parameter key.
   *
   * @returns bool
   *   TRUE if the identity parameter has been set.
   */
  public function hasIdentityParameter($key);

  /**
   * Gets the value of an identity parameter.
   *
   * @param string $key
   *   The parameter key.
   *
   * @return mixed
   *   The identity parameter value.
   */
  public function getIdentityParameter($key);

  /**
   * Gets an associative array of all identity parameters.
   *
   * @returns array
   *   An array of all $key => $value pairs in the identity set that are valid
   *   identity parameters.
   */
  public function getIdentityParameters();

  /**
   * Load a stats element by its id.
   *
   * @param int $elid
   *   The element Id.
   * @param bool $reset
   *   Reset the static cache.
   *
   * @return TetherStatsElementInterface|null
   *   The element object or null if no element was found.
   */
  public static function loadElement($elid, $reset);

  /**
   * Load a stats element by its identity set.
   *
   * @param TetherStatsIdentitySetInterface $identity_set
   *   The collection of parameters which uniquely identify an element.
   *
   * @return TetherStatsElementInterface|null
   *   The element object or null if no element was found.
   */
  public static function loadElementFromIdentitySet(TetherStatsIdentitySetInterface $identity_set);

  /**
   * Construct a TetherStatsElement object from a given identity set.
   *
   * Loads the stats element data from the database for the element that is
   * identified by the $identity_set. If no element currently exists then
   * an entry will be added to the tether_stats_element table.
   *
   * Element data may also be updated with values from the $identity_set if
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
   * @see \Drupal\tether_stats\TetherStatsStorage::createElementFromIdentitySet()
   */
  public static function createElementFromIdentitySet(TetherStatsIdentitySetInterface $identity_set);

}
