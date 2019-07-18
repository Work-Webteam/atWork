<?php

namespace Drupal\tether_stats\Chart;

use Drupal\tether_stats\TetherStatsAnalyticsStorageInterface;

/**
 * Class for a pie chart.
 *
 * Compiles stats data for a chart as described by a
 * TetherStatsPieChartSchema object.
 *
 * @see TetherStatsPieChartSchema
 */
class TetherStatsPieChart extends TetherStatsChart {

  /**
   * The end of the data period to chart.
   *
   * @var \DateTime
   */
  protected $dateFinish;

  /**
   * Builds a new TetherStatsPieChart object based on the given schema.
   *
   * @param TetherStatsPieChartSchema $schema
   *   The schema object which describes what kind of chart to build.
   * @param \DateTime $date_start
   *   The start time for the data period.
   * @param \Drupal\tether_stats\TetherStatsAnalyticsStorageInterface $storage
   *   The analytics storage.
   */
  public function __construct(TetherStatsPieChartSchema $schema, \DateTime $date_start, TetherStatsAnalyticsStorageInterface $storage) {

    $this->dateFinish = clone $date_start;
    $this->dateFinish->add($schema->domainInterval);

    parent::__construct($schema, $date_start, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {

    $has_data = FALSE;

    if (!empty($this->dataMatrix)) {

      foreach ($this->dataMatrix as $value) {

        if (isset($value)) {

          $has_data = TRUE;
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
   *   An array of $label to $value pairs. One for each slice.
   */
  public function getDataTable() {

    $this->loadData();

    $data_table = [];

    foreach ($this->dataMatrix as $inx => $value) {

      $data_table[] = [$this->schema->getChartableItemSpec($inx)['title'], $value];
    }

    return $data_table;
  }

  /**
   * Populates the dataMatrix.
   *
   * Queries data based on the column and series types added.
   */
  protected function calcDataMatrix() {

    foreach ($this->schema->getChartableItemSpec() as $inx => $column) {

      $this->calcSliceData($inx, $column);
    }
  }

  /**
   * Populates the dataMatrix with data for the given column.
   *
   * @param int $data_inx
   *   The index of the dataMatrix where to insert the column data.
   * @param array $slice
   *   An array specifying the slice from the Schema object.
   */
  protected function calcSliceData($data_inx, array $slice) {

    $analytics_callback_arguments = $slice['callback arguments'];

    $analytics_callback_arguments = array_merge($analytics_callback_arguments, [
      $this->dateStart->getTimestamp(),
      $this->dateFinish->getTimestamp(),
    ]);

    $count = call_user_func_array([$this->storage, $slice['analytics callback']], $analytics_callback_arguments);

    $this->dataMatrix[$data_inx] = (int) $count;
  }

}
