<?php

namespace Drupal\tether_stats\Chart;

use Drupal\tether_stats\TetherStatsAnalytics;
use Drupal\tether_stats\TetherStatsAnalyticsStorageInterface;

/**
 * Base schema class that describes a chart with domain steps.
 *
 * Extend this class to add chart specific schemas.
 */
class TetherStatsPieChartSchema extends TetherStatsChartSchema implements TetherStatsChartableItemCollectionInterface, TetherStatsChartIteratorInterface {
  use TetherStatsChartableItemCollectionTrait;

  /**
   * A length of the domain for the chart data.
   *
   * @var \DateInterval
   */
  public $domainInterval;

  /**
   * The human readable label for the slices.
   *
   * @var string
   */
  public $sliceLabel;

  /**
   * The human readable label for the value of the slices.
   *
   * This is something like "Total Hits".
   *
   * @var string
   */
  public $valueLabel;

  /**
   * Constructs a new chart schema with the specified $chart_id.
   *
   * @param string $chart_id
   *   The unique id of the chart. Must not contain any special characters as
   *   this will be used as a javascript variable.
   * @param \DateInterval $domain_interval
   *   A DateInterval for the length of the domain for the chart.
   * @param string $slice_label
   *   (Optional) The label describing what the slices represent in the chart.
   * @param string $value_label
   *   (Optional) The label describing the unit value of the slices such as
   *   "Total Hits".
   */
  public function __construct($chart_id, \DateInterval $domain_interval, $slice_label = '', $value_label = '') {

    parent::__construct($chart_id);

    $this->domainInterval = $domain_interval;
    $this->sliceLabel = $slice_label;
    $this->valueLabel = $value_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {

    return 'tether_stats-chart-pie';
  }

  /**
   * {@inheritdoc}
   */
  public function createChart(\DateTime $date_start, TetherStatsAnalyticsStorageInterface $storage) {

    return new TetherStatsPieChart($this, $date_start, $storage);
  }


  /**
   * {@inheritdoc}
   */
  public function previousDateTime(\DateTime $iterator_time) {

    $previous_time = clone $iterator_time;
    $previous_time->sub($this->domainInterval);

    return $previous_time;
  }

  /**
   * {@inheritdoc}
   */
  public function nextDateTime(\DateTime $iterator_time) {

    $next_time = clone $iterator_time;
    $next_time->add($this->domainInterval);

    return $next_time;
  }

}
