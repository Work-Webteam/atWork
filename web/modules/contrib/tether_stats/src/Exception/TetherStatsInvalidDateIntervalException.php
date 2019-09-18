<?php

namespace Drupal\tether_stats\Exception;

/**
 * Exceptions thrown when an invalid start and end date are provided.
 *
 * This exception is thrown when a start and end date are provided to specify
 * a time interval but the interval is invalid. This can happen if the start
 * date comes on or after the end date.
 */
class TetherStatsInvalidDateIntervalException extends \Exception {

}
