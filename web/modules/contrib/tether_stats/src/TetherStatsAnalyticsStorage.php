<?php

namespace Drupal\tether_stats;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Defines a storage class for mining analytics data.
 */
class TetherStatsAnalyticsStorage implements TetherStatsAnalyticsStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a TetherStatsAnalyticsStorage object.
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
  public function getTopElementsForActivity($activity_type, $start = NULL, $finish = NULL, $limit = 5) {

    $select = $this->database->select('tether_stats_hour_count', 'c')
      ->fields('c', [
        'elid',
      ])
      ->condition('type', $activity_type);

    if (isset($start)) {

      $select->condition('hour', $start, '>=');

    }

    if (isset($finish)) {

      $select->condition('hour', $finish, '<');
    }

    $select->addExpression('SUM(count)', 'count');

    $select->groupBy('elid')
      ->orderBy('count', 'DESC')
      ->range(0, $limit);

    return $select->execute()->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllActivityCount($activity_type, $start, $finish, $step = NULL) {

    $select = $this->database->select('tether_stats_hour_count', 'c')
      ->condition('type', $activity_type)
      ->condition('hour', $start, '>=')
      ->condition('hour', $finish, '<');

    $select->addExpression('SUM(count)', 'count');

    return $this->executeDataQuery($select, $step);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementActivityCount($elid, $activity_type, $start, $finish, $step = NULL) {

    $select = $this->database->select('tether_stats_hour_count', 'c')
      ->condition('elid', $elid)
      ->condition('type', $activity_type)
      ->condition('hour', $start, '>=')
      ->condition('hour', $finish, '<');

    $select->addExpression('SUM(count)', 'count');

    return $this->executeDataQuery($select, $step);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementImpressedOnElementCount($elid_impressed, $elid_impressed_on, $start, $finish, $step = NULL) {

    $select = $this->database->select('tether_stats_impression_log', 'i');
    $select->join('tether_stats_activity_log', 'a', 'i.alid = a.alid');

    $select->condition('i.elid', $elid_impressed)
      ->condition('a.elid', $elid_impressed_on)
      ->condition('a.created', $start, '>=')
      ->condition('a.created', $finish, '<');

    $select->addExpression('COUNT(*)', 'count');

    return $this->executeDataQuery($select, $step, 'a');
  }

  /**
   * {@inheritdoc}
   */
  public function getElementImpressedOnNodeBundleCount($elid_impressed, $bundle, $start, $finish, $step = NULL) {

    $select = $this->database->select('tether_stats_impression_log', 'i');
    $select->join('tether_stats_activity_log', 'a', 'i.alid = a.alid');
    $select->join('tether_stats_element', 'e', "a.elid = e.elid AND e.entity_type = 'node'");
    $select->join('node', 'n', 'e.entity_id = n.nid');

    $select->condition('i.elid', $elid_impressed)
      ->condition('n.type', $bundle, '=')
      ->condition('a.created', $start, '>=')
      ->condition('a.created', $finish, '<');

    $select->addExpression('COUNT(*)', 'count');

    return $this->executeDataQuery($select, $step, 'a');
  }

  /**
   * {@inheritdoc}
   */
  public function getElementImpressedOnBaseUrlCount($elid_impressed, $base_url, $start, $finish, $step = NULL) {

    $select = $this->database->select('tether_stats_impression_log', 'i');
    $select->join('tether_stats_activity_log', 'a', 'i.alid = a.alid');
    $select->join('tether_stats_element', 'e', "a.elid = e.elid");

    $select->condition('i.elid', $elid_impressed)
      ->condition('e.url', $this->database->escapeLike($base_url) . '%', 'LIKE')
      ->condition('a.created', $start, '>=')
      ->condition('a.created', $finish, '<');

    $select->addExpression('COUNT(*)', 'count');

    return $this->executeDataQuery($select, $step, 'a');
  }

  /**
   * {@inheritdoc}
   */
  public function getElementImpressedAnywhereCount($elid_impressed, $start, $finish, $step = NULL) {

    $select = $this->database->select('tether_stats_hour_count', 'c')
      ->condition('elid', $elid_impressed)
      ->condition('type', 'impression')
      ->condition('hour', $start, '>=')
      ->condition('hour', $finish, '<');

    $select->addExpression('SUM(count)', 'count');

    return $this->executeDataQuery($select, $step);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllElementsImpressedOnElementCount($elid_impressed_on, $start, $finish, $step = NULL) {

    $select = $this->database->select('tether_stats_impression_log', 'i');
    $select->join('tether_stats_activity_log', 'a', 'i.alid = a.alid');

    $select->condition('a.elid', $elid_impressed_on)
      ->condition('a.created', $start, '>=')
      ->condition('a.created', $finish, '<');

    $select->addExpression('COUNT(*)', 'count');

    return $this->executeDataQuery($select, $step, 'a');
  }

  /**
   * {@inheritdoc}
   */
  public function getHitActivityWithReferrerCount($referrer, $start, $finish, $step = NULL) {

    $referrer = $this->database->escapeLike($referrer);

    $select = $this->database->select('tether_stats_activity_log', 'a')
      ->condition('a.type', TetherStatsAnalytics::ACTIVITY_HIT)
      ->condition('a.referrer', "%{$referrer}%", 'LIKE')
      ->condition('a.created', $start, '>=')
      ->condition('a.created', $finish, '<');

    $select->addExpression('COUNT(*)', 'count');

    return $this->executeDataQuery($select, $step, 'a');
  }

  /**
   * {@inheritdoc}
   */
  public function getElementHitActivityWithReferrerCount($elid, $referrer, $start, $finish, $step = NULL) {

    $referrer = $this->database->escapeLike($referrer);

    $select = $this->database->select('tether_stats_activity_log', 'a')
      ->condition('a.elid', $elid)
      ->condition('a.type', TetherStatsAnalytics::ACTIVITY_HIT)
      ->condition('a.referrer', "%{$referrer}%", 'LIKE')
      ->condition('a.created', $start, '>=')
      ->condition('a.created', $finish, '<');

    $select->addExpression('COUNT(*)', 'count');

    return $this->executeDataQuery($select, $step, 'a');
  }

  /**
   * {@inheritdoc}
   */
  public function getHitActivityWithBrowserCount($browser, $start, $finish, $step = NULL) {

    $browser = $this->database->escapeLike($browser);

    $select = $this->database->select('tether_stats_activity_log', 'a')
      ->condition('a.type', TetherStatsAnalytics::ACTIVITY_HIT)
      ->condition('a.browser', "%{$browser}%", 'LIKE')
      ->condition('a.created', $start, '>=')
      ->condition('a.created', $finish, '<');

    $select->addExpression('COUNT(*)', 'count');

    return $this->executeDataQuery($select, $step, 'a');
  }

  /**
   * {@inheritdoc}
   */
  public function getElementHitActivityWithBrowserCount($elid, $browser, $start, $finish, $step = NULL) {

    $browser = $this->database->escapeLike($browser);

    $select = $this->database->select('tether_stats_activity_log', 'a')
      ->condition('a.elid', $elid)
      ->condition('a.type', TetherStatsAnalytics::ACTIVITY_HIT)
      ->condition('a.browser', "%{$browser}%", 'LIKE')
      ->condition('a.created', $start, '>=')
      ->condition('a.created', $finish, '<');

    $select->addExpression('COUNT(*)', 'count');

    return $this->executeDataQuery($select, $step, 'a');
  }

  /**
   * Completes an executes an analytics data query.
   *
   * This is a helper method that will complete data select queries, which all
   * have common parts, then execute the query.
   *
   * @param SelectInterface $select
   *   The base select query to complete and execute.
   * @param string $step
   *   The domain step to aggregate over as defined in TetherStatsAnalytics.php.
   * @param string $table_alias
   *   The table alias where the step field comes from.
   *
   * @return int|array
   *   Returns the total count if the $step is NULL, otherwise returns an
   *   array of the unixtime of the domain step to the count for that step.
   */
  private function executeDataQuery(SelectInterface $select, $step, $table_alias = 'c') {

    if (isset($step)) {

      $select->addField($table_alias, $step, 'step');
      $select->groupBy('step');
      $select->orderBy('step');

      $result = $select->execute()->fetchAllKeyed();
    }
    else {

      $result = $select->execute()->fetchField();

      if (!isset($result)) {

        $result = 0;
      }
    }

    return $result;
  }

}
