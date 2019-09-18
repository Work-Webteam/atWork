<?php

namespace Drupal\tether_stats\Chart;

use Drupal\tether_stats\TetherStatsAnalyticsStorageInterface;

/**
 * Abstract class for a chart with domain steps.
 */
abstract class TetherStatsSteppedChart extends TetherStatsChart {

  /**
   * The DateTime marking when the data period ends.
   *
   * @var \DateTime
   */
  protected $dateFinish;

  /**
   * Builds a new TetherStatsSteppedChart object based on the given schema.
   *
   * @param TetherStatsSteppedChartSchema $schema
   *   The schema object which describes what kind of chart to build.
   * @param \DateTime $date_start
   *   The start time for the data period.
   * @param \Drupal\tether_stats\TetherStatsAnalyticsStorageInterface $storage
   *   The analytics storage.
   */
  public function __construct(TetherStatsSteppedChartSchema $schema, \DateTime $date_start, TetherStatsAnalyticsStorageInterface $storage) {

    parent::__construct($schema, $date_start, $storage);

    TetherStatsSteppedChartSchema::normalizeDate($this->schema->domainStep, $this->dateStart);
    $this->dateFinish = clone $this->dateStart;
    $this->schema->addStepSize($this->dateFinish, $this->schema->domainTicks);
  }

}
