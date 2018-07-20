<?php
/**
 * @file
 * Contains \Drupal\hello_world\Controller\HelloController.
 */
namespace Drupal\atwork_idir_update\Controller;
use Drupal\atwork_idir_update\AtworkIdirUpdateLogSplit;
use Drupal\atwork_idir_update\AtworkIdirAddUpdate;
use Drupal\atwork_idir_update\AtworkIdirDelete;
use Drupal\atwork_idir_update\AtworkIdirLog;
class AtworkIdirUpdateController {
  protected $timestamp;
  protected $drupal_path;
  function __construct()
  {
    // Use timestamp and drupal_path mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    $this->drupal_path = drupal_get_path('module','atwork_idir_update');  
  }


  public function content() {
    // Secondary way to run the idir script for testing - or if cron hook does not fire or errors for some reason.
    $run_cron = $this->AtworkIdirInit();
    return array(
      '#type' => 'markup',
      '#markup' => t('<p>Running Cron ' . $run_cron . '.</p>'),
    );
  }

  private function AtworkIdirInit()
  {
    // Set up the logs
    $split_status = $this->splitIdirLogs();
    // Unless we mark this as success, send logs and exit script.
    $split_status == 'success'?(AtworkIdirLog::success('Logs were successfully split')):$this->sendNotifications();
    // file has been split, so now it is time to parse the .tsv files
    // First run the delete script 
    $delete_status = $this->parseFiles('delete');
    $delete_status == "success"?(AtworkIdirLog::success('The delete script finished successfully')):$this->sendNotifications();
    // Second is update
    $update_status = $this->parseFiles('update');
    $update_status == "success"?(AtworkIdirLog::success('The update script finished successfully')):$this->sendNotifications();
    // Finally is Add
    $update_status = $this->parseFiles('add');
    $update_status == "success"?(AtworkIdirLog::success('The add script finished successfully')):$this->sendNotifications();
    // Finally send notifications
    $this->sendNotifications();
    AtworkIdirLog::success($update_status);
  }

  private function splitIdirLogs(){
    $file_handle = new AtworkIdirUpdateLogSplit;
    $filename = 'idir_' . $this->timestamp . '.tsv';

    try
    {
      $full_list = fopen($this->drupal_path . '/idir/' . $filename, 'rb');
      // Check if the file was opened properly.
      if( !$full_list )
      {
        throw new \exception("Failed to open file at atwork_idir_update/idir/" . $filename . '. Script was terminated in Controller.');
      } 
    }
    catch ( Exception $e ) 
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well
      AtworkIdirLog::errorCollect($e);
      return 'fail';
    }
    // If we get here - we have a file to split - so lets move on.
    try
    {
      $check = $file_handle->splitFile();
      if( !$check )
      {
        throw new \exception("Error splitting up the user .tsv");
      }
    }
    catch ( Exception $e )
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well
      AtworkIdirLog::errorCollect($e);
      return 'fail';
    }
    unset($file_handle);
    return 'success';
  }

  private function parseFiles($type){

    $CurrentList = $type=='delete' ? new AtworkIdirDelete() : new AtworkAddUpdate();
    $filename = 'idir_' . $this->timestamp . '_' . $type . '.tsv';

    try
    {
      $full_list = fopen($this->drupal_path . '/idir/' . $filename, 'rb');
      // Check if the file was opened properly.
      if( !$full_list )
      {
        throw new \exception("Failed to open file at atwork_idir_update/idir/" . $filename . '. Script was terminated in Controller.');
      } 
    }
    catch ( Exception $e ) 
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well
      AtworkIdirLog::errorCollect($e);
      return 'failed';
    }

    // If we get here - we have a file to parse - so lets move on.
    try
    {
      $check = $type == 'delete' ? $CurrentList->deleteInit() : $CurrentList->initAddUpdate();
      if( !$check )
      {
        throw new \exception("Error parsing the " . $type . ".tsv, or no file present");
      }
    }
    catch ( Exception $e )
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well
      AtworkIdirLog::errorCollect($e);
      return 'failed';
    }
    unset($CurrentList);
    return 'success';
  }

  private function sendNotifications(){
    echo("logs");
    AtworkIdirLog::notify();
    die();
  }
}