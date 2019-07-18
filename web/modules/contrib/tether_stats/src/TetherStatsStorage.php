<?php

namespace Drupal\tether_stats;

use Drupal\Core\Database\Connection;
use Drupal\tether_stats\Entity\TetherStatsDerivative;
use Drupal\Core\Database\Transaction;

/**
 * Defines a storage class for tracking Tether Stats activity.
 */
class TetherStatsStorage implements TetherStatsStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a TetherStatsStorage object.
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function loadElement($elid) {

    $element = NULL;

    $query = $this->database->select('tether_stats_element', 'e')
      ->fields('e')
      ->condition('elid', $elid);

    $element_values = $query->execute()->fetchAssoc();

    if (!empty($element_values)) {

      // Construct the TetherStatsElement object.
      $element = TetherStatsStorage::constructElementFromDatabaseValues($element_values);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function loadElementFromIdentitySet(TetherStatsIdentitySetInterface $identity_set) {

    $element = NULL;

    // This validity check ensures we have all the parameters set for
    // the different types of uniquely defined elements.
    if ($identity_set->isValid()) {

      $select_query = $this->buildElementSelectQueryFromIdentitySet($identity_set);

      $element_values = $select_query->execute()->fetchAssoc();

      if (!empty($element_values)) {

        // Construct the TetherStatsElement object.
        $element = TetherStatsStorage::constructElementFromDatabaseValues($element_values);
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function createElementFromIdentitySet(TetherStatsIdentitySetInterface $identity_set) {

    $config = \Drupal::config('tether_stats.settings');

    $element = NULL;

    // This validity check ensures we have all the parameters set for
    // the different types of uniquely defined elements.
    if ($identity_set->isValid()) {

      $select_query = $this->buildElementSelectQueryFromIdentitySet($identity_set);

      $this->database->startTransaction('tether_stats_element');

      // Attempt to load an existing stats element.
      $element_values = $select_query->execute()->fetchAssoc();

      // If the stats element does not exist then create it.
      if (empty($element_values)) {

        $fields = $identity_set->getIdentityParams();
        $fields['created'] = REQUEST_TIME;
        $fields['changed'] = REQUEST_TIME;
        $fields['last_activity'] = 0;
        $fields['count'] = 0;

        $elid = $this->database->insert('tether_stats_element')
          ->fields($fields)
          ->execute();

        // Define the $element_values array for the element that was just
        // inserted in the database.
        $element_values = $fields;
        $element_values['elid'] = $elid;
      }
      elseif ((REQUEST_TIME - $element_values['changed']) >= $config->get('advanced.element_ttl')) {

        $fields = $identity_set->getIdentityParams();
        $fields['changed'] = REQUEST_TIME;

        // Elements may sometimes need to be updated as entity urls may change.
        // The time to live setting will determine how long an element remains
        // untouched.
        $this->database->update('tether_stats_element')
          ->fields($fields)
          ->condition('elid', $element_values['elid'])
          ->execute();

        // Update the $element_values array accordingly.
        $fields['count'] = $element_values['count'];
        $fields['elid'] = $element_values['elid'];
        $element_values = $fields + $element_values;
      }

      $this->database->popTransaction('tether_stats_element');

      // Construct the TetherStatsElement object.
      $element = TetherStatsStorage::constructElementFromDatabaseValues($element_values);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeUsageCount($derivative) {

    $query = $this->database->select('tether_stats_element', 'e')
      ->condition('derivative', $derivative)
      ->countQuery();

    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function trackActivity($elid, $event_type, $event_time, $ip_address, $session_id, $browser, $referrer = NULL, $uid = NULL) {

    // Normalize the event time to the first of the hour, day, month and
    // year respectively.
    $normalized_times = [
      'hour' => strtotime(date("Y-m-d H:00:00", $event_time)),
      'day' => strtotime(date("Y-m-d", $event_time)),
      'month' => strtotime(date("Y-m-01", $event_time)),
      'year' => strtotime(date("Y-01-01", $event_time)),
    ];

    $activity_fields = [
      'elid' => $elid,
      'type' => $event_type,
      'ip_address' => $ip_address,
      'sid' => $session_id,
      'uid' => $uid,
      'browser' => $browser,
      'referrer' => $referrer,
      'created' => $event_time,
    ] + $normalized_times;

    $this->database->startTransaction('tether_stats_activity');

    $this->incrementHourCount($elid, $event_type, $event_time, $normalized_times);
    $this->incrementElementCount($elid, $event_time);

    $alid = $this->database->insert('tether_stats_activity_log')
      ->fields($activity_fields)
      ->execute();

    $this->database->popTransaction('tether_stats_activity');

    return $alid;
  }

  /**
   * {@inheritdoc}
   */
  public function trackImpression($elid, $alid, $event_time) {

    // Normalize the event time to the first of the hour, day, month and
    // year respectively.
    $normalized_times = [
      'hour' => strtotime(date("Y-m-d H:00:00", $event_time)),
      'day' => strtotime(date("Y-m-d", $event_time)),
      'month' => strtotime(date("Y-m-01", $event_time)),
      'year' => strtotime(date("Y-01-01", $event_time)),
    ];

    $this->database->startTransaction('tether_stats_impression');

    $this->incrementHourCount($elid, 'impression', $event_time, $normalized_times);

    $ilid = $this->database->insert('tether_stats_impression_log')
      ->fields([
        'elid' => $elid,
        'alid' => $alid,
      ])
      ->execute();

    $this->database->popTransaction('tether_stats_impression');

    return $ilid;
  }

  /**
   * Increments the count in the hour count table.
   *
   * Increments the count in the tether_stats_hour_count table for an event
   * which occurred on an element.
   *
   * The hour count table can hold one entry for each permutation of element,
   * event type and hour. Essentially it holds counters every hour which
   * can be used to aggregate data much more quickly than data mining from
   * the tether_stats_activity_log table.
   *
   * @param int $elid
   *   The element elid.
   * @param string $event_type
   *   The event type such as a 'hit' or 'click'.
   * @param int $event_time
   *   The event time.
   * @param array $normalized_times
   *   An array of times normalized to the start of the hour, day, month
   *   and year of the $event_time.
   */
  private function incrementHourCount($elid, $event_type, $event_time, array $normalized_times) {

    // Increment the hour count.
    $hcid = $this->database->select('tether_stats_hour_count', 'c')
      ->fields('c', ['hcid'])
      ->condition('elid', $elid, '=')
      ->condition('type', $event_type, '=')
      ->condition('hour', $normalized_times['hour'], '=')
      ->execute()->fetchField();

    if ($hcid) {

      $this->database->update('tether_stats_hour_count')
        ->fields(['timestamp' => $event_time])
        ->expression('count', 'count + 1')
        ->condition('hcid', $hcid)
        ->execute();
    }
    else {

      $insert_fields = [
        'elid' => $elid,
        'type' => $event_type,
        'count' => 1,
        'timestamp' => $event_time,
      ] + $normalized_times;

      $this->database->insert('tether_stats_hour_count')
        ->fields($insert_fields)
        ->execute();
    }
  }

  /**
   * Increments the count field of an element.
   *
   * @param int $elid
   *   The element to increment.
   * @param int $event_time
   *   The time of the event to update the 'last_activity' field.
   */
  private function incrementElementCount($elid, $event_time) {

    $this->database->update('tether_stats_element')
      ->fields([
        'last_activity' => $event_time,
      ])
      ->expression('count', 'count + 1')
      ->condition('elid', $elid, '=')
      ->execute();
  }

  /**
   * Internal method that constructs a select query from an identity set.
   *
   * The $identity_set is assumed to be valid.
   *
   * @param TetherStatsIdentitySetInterface $identity_set
   *   The identity set to construct the query for.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select object.
   */
  private function buildElementSelectQueryFromIdentitySet(TetherStatsIdentitySetInterface $identity_set) {

    $select_query = $this->database->select('tether_stats_element', 'e');

    $select_query->fields('e');

    if ($identity_set->has('name')) {

      $select_query->condition('e.name', $identity_set->get('name'));
    }
    elseif ($identity_set->has('entity_type')) {

      $select_query->condition('e.entity_type', $identity_set->get('entity_type'));
      $select_query->condition('e.entity_id', $identity_set->get('entity_id'));
    }
    else {

      $select_query->condition('e.url', $identity_set->get('url'));
    }

    if ($identity_set->has('derivative')) {

      $select_query->condition('e.derivative', $identity_set->get('derivative'));
    }
    else {

      $select_query->isNull('e.derivative');
    }

    if ($identity_set->has('query') && $identity_set->get('query')) {

      $select_query->condition('e.query', $identity_set->get('query'));
    }
    else {

      // The query string may define new elements depending on the configuration
      // settings so we need this condition in the case where there is no query
      // string.
      $select_query->isNull('e.query');
    }

    return $select_query;
  }

  /**
   * Internal method that constructs a TetherStatsElement object.
   *
   * Takes an associative array of database values for an element and constructs
   * a TetherStatsElement object from it. All the necessary fields are assumed
   * to exist.
   *
   * @param array $element_values
   *   The table field values for the element.
   *
   * @return TetherStatsElement
   *   The unsaved TetherStatsElement object for the element.
   */
  private static function constructElementFromDatabaseValues(array $element_values) {

    $identity_keys = array_flip(TetherStatsIdentitySet::getAllowableKeys());
    $identity_params = array_intersect_key($element_values, $identity_keys);

    // Remove NULL values from identity set.
    $identity_params = array_filter($identity_params, function($v) {

      return isset($v);
    });

    return new TetherStatsElement($element_values['elid'], $element_values['count'], $element_values['created'], $element_values['changed'], $element_values['last_activity'], $identity_params);
  }

}
