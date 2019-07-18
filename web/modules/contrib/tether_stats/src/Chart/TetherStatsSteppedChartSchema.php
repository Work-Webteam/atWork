<?php

namespace Drupal\tether_stats\Chart;

use DateTime;
use DateInterval;
use Drupal\tether_stats\Exception\TetherStatsInvalidDateIntervalException;
use Drupal\tether_stats\TetherStatsAnalytics;

/**
 * Abstract schema class that describes a chart with domain steps.
 *
 * Extend this class to add chart specific schemas.
 */
abstract class TetherStatsSteppedChartSchema extends TetherStatsChartSchema implements TetherStatsSteppedChartSchemaInterface, TetherStatsChartIteratorInterface {

  /**
   * The human readable label for the horizontal axis.
   *
   * @var string
   */
  public $hAxisLabel;

  /**
   * The human readable label for the vertical axis.
   *
   * @var string
   */
  public $vAxisLabel;

  /**
   * The number of ticks on the domain.
   *
   * The number of domain ticks or x-axis labels the chart should display data
   * for.
   *
   * @var int
   */
  public $domainTicks;

  /**
   * Size of each domain dtep.
   *
   * The base size of the domain step or the span of each x-axis label. Can be
   * one of 'hour', 'day', 'month' or 'year'.
   *
   * The size of each domain tick if $domainStepMultiplier multiplied by this
   * value.
   *
   * @var string
   */
  public $domainStep = FALSE;

  /**
   * Domain step multiplier.
   *
   * The size of each domain tick is $domainStep multiplied by this value.
   *
   * For example, a $domainStep of 'hour' with a $domainStepMultiplier of
   * 2 would produce a chart where each tick is 2 hours.
   *
   * @var int
   */
  public $domainStepMultiplier;

  /**
   * Constructs a new chart schema with the specified $chart_id.
   *
   * The calcDomainStep() method can be used to automatically calculate the
   * domain step and tick variables based on a start and end date.
   *
   * @param string $chart_id
   *   The unique machine name of the chart. Must contain only letters, numbers,
   *   '-' or '_'.
   * @param string $h_axis_label
   *   A human readable label for the horizontal axis.
   * @param string $v_axis_label
   *   A human readable label for the vertical axis.
   * @param string $domain_step
   *   The base size of the domain step or the span of each x-axis label. Can be
   *   one of 'hour', 'day', 'month' or 'year'.
   * @param int $domain_step_multiplier
   *   A multiplier for the domain step to increase the bucket size of each
   *   tick. For example, a $domainStep of 'hour' with a $domainStepMultiplier
   *   of 2 would produce a chart where each tick is 2 hours.
   * @param int $domain_ticks
   *   The number of domain ticks or x-axis labels the chart should display
   *   data for.
   *
   * @see \Drupal\tether_stats\Chart\TetherStatsSteppedChartSchema::calcDomainStep()
   */
  public function __construct($chart_id, $h_axis_label, $v_axis_label, $domain_step = TetherStatsAnalytics::STEP_HOUR, $domain_step_multiplier = 1, $domain_ticks = 24) {

    $this->domainTicks = $domain_ticks;
    $this->domainStep = $domain_step;
    $this->domainStepMultiplier = $domain_step_multiplier;
    $this->hAxisLabel = $h_axis_label;
    $this->vAxisLabel = $v_axis_label;

    parent::__construct($chart_id);
  }

  /**
   * {@inheritdoc}
   */
  public function calcDomainStep(DateTime $date_start, DateTime $date_finish, $max_domain_ticks = 12, $domain_step_threshold = 32) {

    if ($date_start > $date_finish) {

      throw new TetherStatsInvalidDateIntervalException();
    }

    $timestamp_diff = $date_finish->getTimestamp() - $date_start->getTimestamp();

    foreach (TetherStatsAnalytics::getAllStepOptions() as $step_size) {

      switch ($step_size) {

        case TetherStatsAnalytics::STEP_HOUR:

          $this->domainTicks = floor($timestamp_diff / 3600);
          break;

        case TetherStatsAnalytics::STEP_DAY:
          TetherStatsSteppedChartSchema::normalizeDate(TetherStatsAnalytics::STEP_DAY, $date_start);
          $date_diff = $date_finish->diff($date_start);
          $this->domainTicks = $date_diff->days;
          break;

        case TetherStatsAnalytics::STEP_MONTH:
          TetherStatsSteppedChartSchema::normalizeDate(TetherStatsAnalytics::STEP_MONTH, $date_start);
          $date_diff = $date_finish->diff($date_start);
          $this->domainTicks = 12 * $date_diff->y + $date_diff->m;
          break;

        case TetherStatsAnalytics::STEP_YEAR:
          TetherStatsSteppedChartSchema::normalizeDate(TetherStatsAnalytics::STEP_YEAR, $date_start);
          $date_diff = $date_finish->diff($date_start);
          $this->domainTicks = $date_diff->y;
          break;

      }

      $this->domainStep = $step_size;

      if ($this->domainTicks <= $domain_step_threshold) {

        // Make sure we have at least one domain tick.
        if ($this->domainTicks == 0) {

          $this->domainTicks = 1;
        }

        break;
      }
    }

    // Reset the domain step multiplier.
    $this->domainStepMultiplier = 1;

    while ($this->domainStepMultiplier * $max_domain_ticks < $this->domainTicks) {

      $this->domainStepMultiplier++;
    }

    // Reduce the domain ticks based on the new multiplier. The domainTicks
    // is the actual number of ticks on the horizontal axis that will appear
    // on the chart.
    $this->domainTicks = ceil($this->domainTicks / $this->domainStepMultiplier);
  }

  /**
   * {@inheritdoc}
   */
  public function addStepSize(DateTime $date, $ticks = 1) {

    // Adjust the base tick value by applying the multiplier.
    $ticks *= $this->domainStepMultiplier;

    TetherStatsSteppedChartSchema::addInterval($this->domainStep, $date, $ticks);
  }

  /**
   * {@inheritdoc}
   */
  public function subStepSize(DateTime $date, $ticks = 1) {

    // Adjust the base tick value by applying the multiplier.
    $ticks *= $this->domainStepMultiplier;

    TetherStatsSteppedChartSchema::subInterval($this->domainStep, $date, $ticks);
  }

  /**
   * {@inheritdoc}
   */
  public function previousDateTime(DateTime $iterator_time) {

    $previous_time = clone $iterator_time;
    TetherStatsSteppedChartSchema::subStepSize($previous_time, $this->domainTicks);

    return $previous_time;
  }
  /**
   * {@inheritdoc}
   */
  public function nextDateTime(DateTime $iterator_time) {

    $next_time = clone $iterator_time;
    TetherStatsSteppedChartSchema::addStepSize($next_time, $this->domainTicks);

    return $next_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function normalizeDate($domain_step, DateTime &$date) {

    switch ($domain_step) {

      case TetherStatsAnalytics::STEP_HOUR:
        $date = new DateTime($date->format('Y-m-d H:00:00'));
        break;

      case TetherStatsAnalytics::STEP_DAY:
        $date = new DateTime($date->format('Y-m-d 00:00:00'));
        break;

      case TetherStatsAnalytics::STEP_MONTH:
        $date = new DateTime($date->format('Y-m-01 00:00:00'));
        break;

      case TetherStatsAnalytics::STEP_YEAR:
        $date = new DateTime($date->format('Y-01-01 00:00:00'));
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function addInterval($domain_step, DateTime $date, $ticks = 1) {

    // Add the given step size to the date depending on the step type.
    switch ($domain_step) {

      case TetherStatsAnalytics::STEP_HOUR:
        $date->add(new DateInterval("PT{$ticks}H"));
        break;

      case TetherStatsAnalytics::STEP_DAY:
        $date->add(new DateInterval("P{$ticks}D"));
        break;

      case TetherStatsAnalytics::STEP_MONTH:
        $date->add(new DateInterval("P{$ticks}M"));
        break;

      case TetherStatsAnalytics::STEP_YEAR:
        $date->add(new DateInterval("P{$ticks}Y"));
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function subInterval($domain_step, DateTime $date, $ticks = 1) {

    // Subtract the given step size from the date depending on the step type.
    switch ($domain_step) {

      case TetherStatsAnalytics::STEP_HOUR:
        $date->sub(new DateInterval("PT{$ticks}H"));
        break;

      case TetherStatsAnalytics::STEP_DAY:
        $date->sub(new DateInterval("P{$ticks}D"));
        break;

      case TetherStatsAnalytics::STEP_MONTH:
        $date->sub(new DateInterval("P{$ticks}M"));
        break;

      case TetherStatsAnalytics::STEP_YEAR:
        $date->sub(new DateInterval("P{$ticks}Y"));
        break;

    }
  }

}
