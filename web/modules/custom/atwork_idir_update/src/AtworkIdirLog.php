<?php

namespace Drupal\atwork_idir_update;

/**
 * Class AtworkIdirLog.
 *
 * @package Drupal\atwork_idir_update
 */
class AtworkIdirLog {

  /**
   * Errors that are caught will be forwarded here & added to the error log.
   *
   * @param string $error
   *   A string telling us what error we experienced.
   */
  public static function errorCollect($error) {
    \Drupal::logger('AtworkIdirUpdate')->error($error);
  }

  /**
   * Logs successful executions.
   *
   * @param string $complete
   *   A string we will send when an action is completed successfully.
   */

  public static function success($complete) {
    \Drupal::logger('AtworkIdirUpdate')->notice($complete);
  }

  /**
   * Not currently used - meant to email admin.
   */
  public static function notify() {
    // TODO: Find and email errorlog and update log.
    return "Notifications sent successfully. Cron complete.";
  }

}
