<?php

namespace Drupal\tether_stats\Chart;

use Drupal\tether_stats\TetherStatsAnalyticsStorageInterface;
use Drupal\tether_stats\TetherStatsAnalytics;
use Alpha\A;

/**
 * Class for a combo chart which uses both columns and line series.
 *
 * Compiles stats data for a chart as described by a
 * TetherStatsComboChartSchema object.
 *
 * @see TetherStatsComboChartSchema
 */
class TetherStatsComboChart extends TetherStatsSteppedChart {

  /**
   * Builds a new TetherStatsComboChart object based on the given schema.
   *
   * @param TetherStatsComboChartSchema $schema
   *   The schema object which describes what kind of chart to build.
   * @param \DateTime $date_start
   *   The start time for the data period.
   * @param \Drupal\tether_stats\TetherStatsAnalyticsStorageInterface $storage
   *   The analytics storage.
   */
  public function __construct(TetherStatsComboChartSchema $schema, \DateTime $date_start, TetherStatsAnalyticsStorageInterface $storage) {

    parent::__construct($schema, $date_start, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {

    $has_data = FALSE;

    if (!empty($this->dataMatrix)) {

      foreach ($this->dataMatrix as $column_values) {

        $has_data = !empty($column_values);

        if ($has_data) {

          break;
        }
      }
    }

    return $has_data;
  }

  /**
   * Convert the raw dataMatrix into a chart data table.
   *
   * @return array
   *   A non-associative array of domain value rows as follows:
   *     - unixtime
   *     - value of column A
   *     - value of column B
   *     ...
   */
  public function getDataTable() {

    $this->loadData();
    $data_table = [];

    for ($start = clone $this->dateStart; $start < $this->dateFinish; $this->schema->addStepSize($start)) {

      if (!isset($this->dataMatrix[$start->getTimestamp()])) {

        $this->dataMatrix[$start->getTimestamp()] = [];
      }
      $row = $this->dataMatrix[$start->getTimestamp()];

      array_unshift($row, $start->getTimestamp());
      $data_table[] = $row;
    }

    return $data_table;
  }

  /**
   * Gets a transpose of the two-dimensional $dataMatrix array.
   *
   * @return array
   *   A transpose of the dataMatrix property.
   */
  protected function getTransposedDataMatrix() {

    $this->loadData();
    $transposed_data = [];

    $num_data_items = count($this->schema->getChartableItemSpec()) + count($this->schema->getSeriesSpec());

    for ($i = 0; $i < $num_data_items; $i++) {

      $transposed_data[$i] = [];

      foreach ($this->dataMatrix as $step => $row) {

        $transposed_data[$i][$step] = $this->dataMatrix[$step][$i];
      }
    }
    return $transposed_data;
  }

  /**
   * Populates the dataMatrix based on the schema.
   *
   * Queries data based on the column and series types added.
   */
  protected function calcDataMatrix() {

    $columns = $this->schema->getChartableItemSpec();
    $series = $this->schema->getSeriesSpec();

    $this->dataMatrix = [];

    $num_columns = count($columns);

    foreach ($columns as $inx => $column) {

      $this->calcColumnData($inx, $column);
    }

    foreach ($series as $inx => $line_series) {

      $this->calcSeriesData($inx + $num_columns, $line_series);
    }
  }

  /**
   * Populates the dataMatrix with data for the given column.
   *
   * @param int $data_inx
   *   The index of the data matrix to where the column data applies.
   * @param array $column
   *   The array specification for the column from the Schema object.
   */
  protected function calcColumnData($data_inx, array $column) {

    $analytics_callback_arguments = $column['callback arguments'];

    $analytics_callback_arguments = array_merge($analytics_callback_arguments, [
      $this->dateStart->getTimestamp(),
      $this->dateFinish->getTimestamp(),
      $this->schema->domainStep,
    ]);

    $column_data = call_user_func_array([$this->storage, $column['analytics callback']], $analytics_callback_arguments);

    if ($column_data !== FALSE) {

      $start = clone $this->dateStart;
      $next = clone $start;
      $this->schema->addStepSize($next);

      $this->dataMatrix[$start->getTimestamp()][$data_inx] = 0;

      foreach ($column_data as $step => $count) {

        // Initialize the count and fill in zero values for step times when
        // there is no data.
        while ($step >= $next->getTimestamp()) {

          $this->schema->addStepSize($start);
          $this->schema->addStepSize($next);
          $this->dataMatrix[$start->getTimestamp()][$data_inx] = 0;
        }

        $this->dataMatrix[$start->getTimestamp()][$data_inx] += (int) $count;
      }
      $this->schema->addStepSize($start);

      while ($start < $this->dateFinish) {

        $this->dataMatrix[$start->getTimestamp()][$data_inx] = 0;
        $this->schema->addStepSize($start);
      }
    }

  }

  /**
   * Calculate and add line series data to the data matrix.
   *
   * @param int $data_inx
   *   The index in the data matrix where the line series data is to be
   *   inserted.
   * @param array $line_series
   *   The array of options describing the line series from the schema.
   */
  protected function calcSeriesData($data_inx, array $line_series) {

    switch ($line_series['type']) {

      case TetherStatsComboChartSchemaInterface::SERIES_SUMMATION;
        $this->calcTotalData($data_inx);
        break;

      case TetherStatsComboChartSchemaInterface::SERIES_MEAN;
        $this->calcMeanData($data_inx);
        break;

    }
  }

  /**
   * Calculates values in the dataMatrix for a summation line series.
   *
   * Sums the data count for each column in the dataMatrix as determined by the
   * "columns" property. Each column must already be calculated at this point.
   *
   * @param int $data_inx
   *   The index in the data matrix where the line series data is to be
   *   inserted.
   */
  protected function calcTotalData($data_inx) {

    $start = clone $this->dateStart;

    while ($start < $this->dateFinish) {

      $count = 0;
      $num_columns = count($this->schema->getChartableItemSpec());

      for ($i = 0; $i < $num_columns; $i++) {

        $count += $this->dataMatrix[$start->getTimestamp()][$i];
      }

      $this->dataMatrix[$start->getTimestamp()][$data_inx] = $count;
      $this->schema->addStepSize($start);
    }
  }

  /**
   * Calculates values in the dataMatrix for a mean line series.
   *
   * Averages the data count for each column in the dataMatrix as determined by
   * the "columns" property. Each column must already be calculated at this
   * point.
   *
   * @param int $data_inx
   *   The index in the data matrix where the line series data is to be
   *   inserted.
   */
  protected function calcMeanData($data_inx) {

    $start = clone $this->dateStart;

    while ($start < $this->dateFinish) {

      $count = 0;
      $num_columns = count($this->schema->getChartableItemSpec());

      for ($i = 0; $i < $num_columns; $i++) {

        $count += $this->dataMatrix[$start->getTimestamp()][$i];
      }

      $this->dataMatrix[$start->getTimestamp()][$data_inx] = ($count / $num_columns);
      $this->schema->addStepSize($start);
    }
  }

}
