<?php

/**
 * @file
 * Contains \Drupal\tether_stats\Chart\TetherStatsComboChartSchema.
 */

namespace Drupal\tether_stats\Chart;

use Drupal\tether_stats\TetherStatsAnalytics;
use Drupal\tether_stats\TetherStatsAnalyticsStorageInterface;

/**
 * Schema class for a Combo Chart.
 *
 * Defines how a chart is to be generated by the TetherStatsComboChart
 * class.
 *
 * @see TetherStatsChartsComboChart
 */
class TetherStatsComboChartSchema extends TetherStatsSteppedChartSchema implements TetherStatsComboChartSchemaInterface {
  use TetherStatsChartableItemCollectionTrait;

  /**
   * The line series specification.
   *
   * Line series to be added to the chart which aggregate over column values
   * added to the chart.
   *
   * @var array
   */
  protected $series = [];

  /**
   * {@inheritdoc}
   */
  public function getSeriesSpec($index = NULL) {

    if (isset($index)) {

      return $this->series[$index];
    }
    return $this->series;
  }

  /**
   * Gets the title for the line series.
   *
   * @param int $index
   *   (Optional) The index of the line series title to return.
   *
   * @return array
   *   The series title. If $index is NULL, an array of titles
   *   for all line series will be returned.
   */
  public function getSeriesTitle($index = NULL) {

    if (isset($index)) {

      return $this->series[$index]['title'];
    }
    else {

      $titles = [];

      foreach ($this->series as $item) {

        $titles[] = $item['title'];
      }
      return $titles;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {

    return 'tether_stats-chart-combo';
  }

  /**
   * {@inheritdoc}
   */
  public function createChart(\DateTime $date_start, TetherStatsAnalyticsStorageInterface $storage) {

    return new TetherStatsComboChart($this, $date_start, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public function addSummationLineSeries($title) {

    $this->series[] = [
      'type' => TetherStatsComboChartSchemaInterface::SERIES_SUMMATION,
      'title' => $title,
    ];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addMeanLineSeries($title) {

    $this->series[] = [
      'type' => TetherStatsComboChartSchemaInterface::SERIES_MEAN,
      'title' => $title,
    ];
    return $this;
  }

}