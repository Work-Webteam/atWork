<?php

namespace Drupal\tether_stats;

/**
 * Defines constants for generating analytics data.
 */
final class TetherStatsAnalytics {

  /**
   * The stats activity for a page "hit".
   *
   * @var string
   */
  const ACTIVITY_HIT = 'hit';

  /**
   * The stats activity for a "click".
   *
   * @var string
   */
  const ACTIVITY_CLICK = 'click';

  /**
   * The stats activity for an "impression".
   *
   * @var string
   */
  const ACTIVITY_IMPRESS = 'impression';

  /**
   * The domain step size of one hour.
   *
   * Used in TetherStatsAnalyticsStorage methods for aggregating data
   * over hourly increments.
   *
   * @var string
   */
  const STEP_HOUR = 'hour';

  /**
   * The domain step size of one day.
   *
   * Used in TetherStatsAnalyticsStorage methods for aggregating data
   * over daily increments.
   *
   * @var string
   */
  const STEP_DAY = 'day';

  /**
   * The domain step size of one month.
   *
   * Used in TetherStatsAnalyticsStorage methods for aggregating data
   * over monthly increments.
   *
   * @var string
   */
  const STEP_MONTH = 'month';

  /**
   * The domain step size of one year.
   *
   * Used in TetherStatsAnalyticsStorage methods for aggregating data
   * over yearly increments.
   *
   * @var string
   */
  const STEP_YEAR = 'year';

  /**
   * Get a list of all domain step fields.
   *
   * @return array
   *   An array of step field options.
   */
  public static function getAllStepOptions() {

    return [
      TetherStatsAnalytics::STEP_HOUR,
      TetherStatsAnalytics::STEP_DAY,
      TetherStatsAnalytics::STEP_MONTH,
      TetherStatsAnalytics::STEP_YEAR,
    ];
  }

}
