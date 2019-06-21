<?php

/**
 * @file
 * Contains Drupal\tether_stats\Exception\TetherStatsIncompleteIdentitySetException.
 */

namespace Drupal\tether_stats\Exception;

/**
 * Exceptions thrown when an identity set is missing required parameters.
 *
 * This exception is thrown when validating a stats element identity set
 * and there aren't enough parameters specified to ensure uniqueness.
 */
class TetherStatsIncompleteIdentitySetException extends \Exception {

}
