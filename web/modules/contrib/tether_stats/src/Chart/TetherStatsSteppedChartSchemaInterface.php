<?php

namespace Drupal\tether_stats\Chart;

use DateTime;

/**
 * Interface for TetherStatsSteppedChartSchema.
 */
interface TetherStatsSteppedChartSchemaInterface {

  /**
   * Set the $domain_step and $domainTicks properties.
   *
   * Calculate and set the $domain_step and $domainTicks properties
   * automatically by adjusting the chart's step size to fit between the two
   * given dates.
   *
   * @param DateTime $date_start
   *   The start date of the data period.
   * @param DateTime $date_finish
   *   The end date of the data period.
   * @param int $max_domain_ticks
   *   The maximum number of ticks to appear in the domain.
   * @param int $domain_step_threshold
   *   The maximum number of base ticks in the domain before increasing to a
   *   larger step size. That is, increasing from hourly, to daily, etc.
   *   Unlike $max_domain_ticks, this excludes any domainStepMultiplier
   *   adjustment to reduce the number of ticks.
   *
   * @throws TetherStatsInvalidDateIntervalException
   *   The $date_start must be less than the $date_finish.
   */
  public function calcDomainStep(DateTime $date_start, DateTime $date_finish, $max_domain_ticks = 12, $domain_step_threshold = 32);

  /**
   * Adds the domain step size to the given date.
   *
   * @param DateTime $date
   *   The date to increase.
   * @param int $ticks
   *   The number of steps the date should be increased by.
   */
  public function addStepSize(DateTime $date, $ticks = 1);

  /**
   * Subtracts the domain step size from the given date.
   *
   * @param DateTime $date
   *   The date to reduce.
   * @param int $ticks
   *   The number of steps the date should be reduced by.
   */
  public function subStepSize(DateTime $date, $ticks = 1);

  /**
   * Normalizes the given date to the domain step size.
   *
   * Descrease the $date parameter such that the domain starts according to a
   * precise fit with the domain_step. The domain_step property must be
   * specified or no changes will occur.
   *
   * @param string $domain_step
   *   The domain step in which to normalize. Can be 'hour', 'day', 'month' or
   *   'year'.
   * @param DateTime $date
   *   The date to normalize.
   */
  public static function normalizeDate($domain_step, DateTime &$date);

  /**
   * Adds the domain step size to the given date.
   *
   * @param string $domain_step
   *   The domain step size. Can be 'hour', 'day', 'month' or 'year'.
   * @param DateTime $date
   *   The date to modify.
   * @param int $ticks
   *   The number of times to add the domain_step size to the $date parameter.
   */
  public static function addInterval($domain_step, DateTime $date, $ticks = 1);

  /**
   * Subtracts the domain step size from the given date.
   *
   * @param string $domain_step
   *   The domain step size. Can be 'hour', 'day', 'month' or 'year'.
   * @param DateTime $date
   *   The date to modify.
   * @param int $ticks
   *   The number of times to subtract the domain_step size from the $date
   *   parameter.
   */
  public static function subInterval($domain_step, DateTime $date, $ticks = 1);

}
