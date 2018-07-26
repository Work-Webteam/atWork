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
use Drupal\atwork_idir_update\AtworkIdirUpdateFTP;
class AtworkIdirUpdateController {
  protected $timestamp;
  protected $drupal_path;
  // FTP credentials - want to make 
  private $username;
  private $password;
  private $hostname;
  private $jail;

  function __construct()
  {
    // Use timestamp and drupal_path mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    $this->drupal_path = drupal_get_path('module','atwork_idir_update');
    $this->username = "psa";
    $this->password = "Men@W0rk";
    $this->hostname = "ftp.dir.gov.bc.ca";
    //$this->hostname = "ftp://142.34.217.168";
    $this->jail = "Public://idir/" . $this->timestamp;
  }


  public function main() {
    set_error_handler(array($this, 'exception_error_handler'));
    $interval = 60 * 2;
    $next_execution = \Drupal::state()->get('atwork_idir_update.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : 0;
    if(REQUEST_TIME >= $next_execution)
    {
      // Secondary way to run the idir script for testing - or if cron hook does not fire or errors for some reason.
      \Drupal::logger('atwork_idir_update')->info('Running the update script');
      // Set time we ran this - and don't let us run it for at least 2 mins to avoid running twice
      \Drupal::state()->set('atwork_idir_update.next_execution', REQUEST_TIME + $interval);

      $run_cron = $this->AtworkIdirInit();
      \Drupal::logger('atwork_idir_update')->info('Idir update ran successfully');
    } 
    else
    {
      \Drupal::logger('atwork_idir_update')->warning('Idir script was run less than two minutes ago - please check if it is still running, or wait 2 minutes before trying again');
      die();
    }
    isset($run_cron)?:$run_cron = "Cron did not run";
    return array(
      '#type' => 'markup',
      '#markup' => t('<p>Cron run status: <h1>' . $run_cron . '.</h1></p>'),
    );
  }

  private function AtworkIdirInit()
  { 
    // TODO: Use FileTransfer
    // TODO: FTP the file here: file_prepare_directory(Public://idir/timestamp/); 
    // Need to pull down the file and put it in a dir.
    // TODO: Add FTP class here once developed
    // filename is idir.tsv 
    // Connection: Directory Synch FTP Server, 142.34.217.168, TCP port 21000-21100 (142.34.217.168  ftp.dir.gov.bc.ca ftp)
    $idir_ftp = new AtworkIdirUpdateFTP($this->username, $this->password, $this->hostname, $this->jail, "21");
    try{
      //$ftp_result = $idir_ftp->getFTPFile($this->timestamp);
      $ftp_result = $idir_ftp->getFTPFile($this->timestamp);
      dpm($ftp_result);
      // Check if the file was opened properly.
      if( !isset($ftp_result) )
      {
        throw new \exception("Failed to get new ftp");
      } 
    }
    catch ( Exception $e ) 
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well
      AtworkIdirLog::errorCollect($e);
      $this->sendNotifications();
    }
    // Set up the logs
    $split_status = $this->splitIdirLogs();
    // Unless we mark this as success, send logs and exit script.
    $split_status == 'success'?(AtworkIdirLog::success('Logs were successfully split')):$this->sendNotifications();
    AtworkIdirLog::success('Beginning to delete old idirs.');

    // file has been split, so now it is time to parse the .tsv files
    // First run the delete script 
    $delete_status = $this->parseFiles('delete');
    $delete_status == "success"?(AtworkIdirLog::success('The delete script finished successfully')):$this->sendNotifications();
    // Second is update
    AtworkIdirLog::success('Beginning to update current Idirs');
    $update_status = $this->parseFiles('update');
    $update_status == "success"?(AtworkIdirLog::success('The update script finished successfully')):$this->sendNotifications();
    // Finally is Add
    AtworkIdirLog::success('Beginning to add new Idirs');
    $update_status = $this->parseFiles('add');
    $update_status == "success"?(AtworkIdirLog::success('The add script finished successfully')):$this->sendNotifications();
    AtworkIdirLog::success("All Idir updates finished successfully");
    // Finally send notifications
    $this->sendNotifications();
    return "Cron ran successfully";
  }

  private function splitIdirLogs(){
    $file_handle = new AtworkIdirUpdateLogSplit;
    $filename = 'idir_' . $this->timestamp . '.tsv';

    try
    {
      $full_list = fopen($this->drupal_path . '/idir/' . $filename, 'rb');
      // Check if the file was opened properly.
      if( !isset($full_list) )
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
    // Found a file, so make sure this is closed:
    if($full_list){
      fclose($full_list);
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

    $CurrentList = $type=='delete' ? new AtworkIdirDelete() : new AtworkIdirAddUpdate();
    $filename = 'idir_' . $this->timestamp . '_' . $type . '.tsv';

    try
    {
      $file_path = $this->drupal_path . '/idir/' . $filename;
      $full_list = fopen($file_path, 'r');
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
      if($type == 'delete')
      {
        $check = $CurrentList->deleteInit(); 
      } elseif($type == 'add')
      {
        $check = $CurrentList->initAddUpdate("add");
      } elseif($type == 'update'){
        $check = $CurrentList->initAddUpdate("update");
      }
      if( !$check )
      {
        throw new \exception("Error parsing the " . $filename . ", or no file present");
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
    AtworkIdirLog::notify();
    die();
  }

  public static function exception_error_handler($severity, $message, $file, $line ) {
    if (!(error_reporting() & $severity)) {
      // This error code is not included in error_reporting
      return;
    }
    AtworkIdirLog::errorCollect($message . " " . $severity . " " . $file . " " . $line . "\n");
    throw new \exception($message . " " . $severity . " " . $file . " " . $line);
  }
}