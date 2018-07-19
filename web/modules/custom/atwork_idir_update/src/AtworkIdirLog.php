<?php
namespace Drupal\atwork_idir_update;

class AtworkIdirLog /* implements iAtworkIdirLog */
{
  /**
   * errorCollect
   * @param : [string] $error any errors that are caught will be forwarded here to be added to the error log (dated)
   * @return void
   */
  public static function errorCollect($error)
  {
    $error_file = fopen(drupal_get_path('module','atwork_idir_update') . '/Logs/errorlog_' . date('Ymd') . '.log', 'a');
    fwrite($error_file, $error);
    fclose($error_file);
  }
  
  /**
   * success
   *
   * @param [string] $complete : a string we will send when an update is completed successfully
   * @return void
   */
  public static function success($complete)
  {
    $idir_update_file = fopen(drupal_get_path('module','atwork_idir_update') . '/Logs/idir_update_log_' . date('Ymd') . '.log', 'a');
    fwrite($idir_update_file, $complete);
    fclose($idir_update_file);
  }

  /**
   * notify: Final function called, simply grabs the current error and success logs and emails them to the site admin - then ends the program
   *
   * @return void
   */
  public static function notify()
  {
    // TODO: Find and email errorlog and update log.
    echo("Send Notify");
  }
}