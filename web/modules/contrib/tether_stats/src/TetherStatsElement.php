<?php

namespace Drupal\tether_stats;

/**
 * Constructs an object that represents a stats element.
 *
 * Stats elements are things to which you activity log entries can be applied
 * and ultimately things you can add a counter for. These will pages for
 * tracking hits on, but they may also represent non-pages for other types of
 *  activity depending on the context.
 *
 * A stats element can stand by itself with a unique identifier, can represent
 * an entity such as a user or node, or it can simply represent a specific url
 * of a page.
 *
 * Derivatives may be used to define multiple unique elements that relate to
 * a single entity. In this way, you can have multiple counters each
 * representing a different part of one thing.
 *
 * This class should not be instantiated directly. Instead use the static
 * createElementFromIdentitySet() method to create or load elements as this
 * will enforce uniqueness of the identity set.
 */
class TetherStatsElement implements TetherStatsElementInterface {

  /**
   * The primary key value for the stats element.
   *
   * @var int
   */
  private $elid;

  /**
   * The hit count total over all time on this element.
   *
   * @var int
   */
  private $count;

  /**
   * The time the element was created.
   *
   * @var int
   */
  private $created;

  /**
   * The last time the element was updated.
   *
   * @var int
   */
  private $changed;

  /**
   * The last time activity occurred for the element.
   *
   * @var int
   */
  private $lastActivity;

  /**
   * The TetherStatsIdentitySetInterface.
   *
   *  A collection of parameters uniquely defining this element.
   *
   * @var TetherStatsIdentitySetInterface
   */
  private $identitySet;

  /**
   * Constructs a TetherStatsElement object.
   *
   * @param int $elid
   *   The element primary key.
   * @param int $count
   *   The total hit activity count for this element.
   * @param int $created
   *   The unix timestamp of when the element was first created.
   * @param int $changed
   *   The unix timestamp of the time the element was last updated.
   * @param int $last_activity
   *   The unix timestamp of the last activity on this element.
   * @param array $identity_parameters
   *   An array of identity set parameter to pass on to TetherStatsIdentitySet.
   */
  public function __construct($elid, $count, $created, $changed, $last_activity, array $identity_parameters = []) {

    $this->elid = $elid;
    $this->count = $count;
    $this->created = $created;
    $this->changed = $changed;
    $this->lastActivity = $last_activity;

    $this->identitySet = new TetherStatsIdentitySet($identity_parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {

    return $this->elid;
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {

    return $this->count;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {

    return $this->created;
  }

  /**
   * {@inheritdoc}
   */
  public function getChanged() {

    return $this->changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastActivity() {

    return $this->lastActivity;
  }

  /**
   * {@inheritdoc}
   */
  public function hasIdentityParameter($key) {

    return $this->identitySet->has($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityParameter($key) {

    return $this->identitySet->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityParameters() {

    return $this->identitySet->getIdentityParams();
  }

  /**
   * {@inheritdoc}
   */
  public static function loadElement($elid, $reset = FALSE) {
    $elements =& drupal_static(__FUNCTION__, []);

    if (!isset($elements[$elid]) || $reset) {

      $storage = \Drupal::service('tether_stats.manager')->getStorage();
      $elements[$elid] = $storage->loadElement($elid);
    }

    return $elements[$elid];
  }

  /**
   * {@inheritdoc}
   */
  public static function loadElementFromIdentitySet(TetherStatsIdentitySetInterface $identity_set) {

    $storage = \Drupal::service('tether_stats.manager')->getStorage();

    return $storage->loadElementFromIdentitySet($identity_set);
  }

  /**
   * {@inheritdoc}
   */
  public static function createElementFromIdentitySet(TetherStatsIdentitySetInterface $identity_set) {

    $storage = \Drupal::service('tether_stats.manager')->getStorage();

    return $storage->createElementFromIdentitySet($identity_set);
  }

}
