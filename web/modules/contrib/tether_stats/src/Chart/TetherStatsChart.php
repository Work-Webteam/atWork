<?php

namespace Drupal\tether_stats\Chart;

use Drupal\tether_stats\TetherStatsAnalyticsStorageInterface;

/**
 * Abstract class for all charts.
 */
abstract class TetherStatsChart {

  /**
   * The TetherStatsChartSchema object used to define this chart.
   *
   * @var \Drupal\tether_stats\Chart\TetherStatsChartSchema
   */
  protected $schema;

  /**
   * The analytics storage.
   *
   * @var \Drupal\tether_stats\TetherStatsAnalyticsStorageInterface
   */
  protected $storage;

  /**
   * The chart's array of data points.
   *
   * @var array
   */
  protected $dataMatrix;

  /**
   * The start of the data period to chart.
   *
   * @var \DateTime
   */
  protected $dateStart;

  /**
   * Builds a new TetherStatsChart object based on the given schema.
   *
   * @param TetherStatsChartSchema $schema
   *   The schema object which describes what kind of chart to build.
   * @param \DateTime $date_start
   *   The start time for the data period.
   * @param \Drupal\tether_stats\TetherStatsAnalyticsStorageInterface $storage
   *   The analytics storage.
   */
  public function __construct(TetherStatsChartSchema $schema, \DateTime $date_start, TetherStatsAnalyticsStorageInterface $storage) {

    $this->schema = $schema;
    $this->storage = $storage;
    $this->dataMatrix = FALSE;
    $this->dateStart = clone $date_start;
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

    return $this->schema->id();
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
  public function getClass() {

    return $this->schema->getClass();
  }

  /**
   * Gets the schema being used by this chart.
   *
   * @return \Drupal\tether_stats\Chart\TetherStatsChartSchema
   *   The chart schema.
   */
  public function getSchema() {

    return $this->schema;
  }

  /**
   * Queries the database and populates the dataMatrix.
   *
   * @param bool $reset
   *   Force the data matrix to reload even if the dataMatrix has already
   *   been populated.
   *
   * @return array
   *   An array containing the raw data matrix. This differs depending
   *   on the type of chart.
   */
  public function loadData($reset = FALSE) {

    if ($this->dataMatrix === FALSE || $reset) {

      $this->dataMatrix = [];
      $this->calcDataMatrix();
    }
    return $this->dataMatrix;
  }

  /**
   * Accessor for the start date.
   */
  public function getDateStart() {

    return clone $this->dateStart;
  }

  /**
   * Determines if this table has any data.
   *
   * @return bool
   *   Will return TRUE if loadData() has not been run, or if the resulting
   *   data has no columns or parts.
   */
  abstract public function hasData();

  /**
   * Gets the data table for this chart.
   *
   * The format of this array depends on the type of chart.
   *
   * @return array|false
   *   The data matrix array or FALSE if no data has been loaded yet.
   *
   * @see TetherStatsChart::loadData()
   */
  abstract public function getDataTable();

  /**
   * Populates the dataMatrix with data based on the schema.
   */
  abstract protected function calcDataMatrix();

}
