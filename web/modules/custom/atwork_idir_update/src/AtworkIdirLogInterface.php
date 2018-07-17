<?php
namespace Drupal\atwork_idir_update;

interface iAtworkIdirLog 
{
  
  /**
   * errorCollect
   * @param : [string] $error any errors that are caught will be forwarded here to be added to the error log (dated)
   * @return void
   */
  static public function errorCollect($error);
  
  /**
   * success
   *
   * @param [string] $complete : a string we will send when an update is completed successfully
   * @return void
   */
  static public function success($complete);

  /**
   * notify: Final function called, simply grabs the current error and success logs and emails them to the site admin - then ends the program
   *
   * @return void
   */
  static public function notify();
}