<?php

namespace Drupal\tether_stats\Chart;

use Drupal\tether_stats\TetherStatsAnalyticsStorageInterface;

/**
 * Abstract class for a chart schema.
 */
abstract class TetherStatsChartSchema {

  /**
   * The unique chart Id.
   *
   * Used on the containing html element and on iteration callbacks to
   * identify this chart.
   *
   * @var string
   */
  protected $id;

  /**
   * Constructs a new chart schema with the specified $chart_id.
   *
   * @param string $chart_id
   *   The unique id of the chart. Must be machine friendly. Used as a
   *   javascript variable.
   */
  public function __construct($chart_id) {

    $this->id = $chart_id;
  }

  /**
   * Gets the unique string id for this chart.
   *
   * Used on the containing html element and on iteration callbacks to
   * identify this chart.
   *
   * @return string
   *   The chart id.
   */
  public function id() {

    return isset($this->id) ? $this->id : NULL;
  }

  /**
   * Gets a CSS class name for this type of chart.
   *
   * The class is to be applied to the HTML container when rendering this
   * chart. Every type of chart will have a unique class name allowing for
   * more granular styling.
   *
   * @return string
   *   A class name for this type of chart.
   */
  abstract public function getClass();

  /**
   * Generates a new chart object from this schema.
   *
   * @param \DateTime $date_start
   *   The start date for the period of data to be displayed by the chart.
   * @param TetherStatsAnalyticsStorageInterface $storage
   *   The analytics storage.
   *
   * @return TetherStatsChart
   *   The chart object.
   */
  abstract public function createChart(\DateTime $date_start, TetherStatsAnalyticsStorageInterface $storage);

}
