<?php

namespace Drupal\atwork_idir_update\Controller;

use Drupal\atwork_idir_update\AtworkIdirUpdateLogSplit;
use Drupal\atwork_idir_update\AtworkIdirAddUpdate;
use Drupal\atwork_idir_update\AtworkIdirDelete;
use Drupal\atwork_idir_update\AtworkIdirLog;
use Drupal\atwork_idir_update\AtworkIdirUpdateFTP;
use Drupal\atwork_idir_update\AtworkIdirUpdateInputMatrix;

/**
 * Class AtworkIdirUpdateController.
 *
 * @package Drupal\atwork_idir_update\Controller
 */
class AtworkIdirUpdateController {
  protected $timestamp;
  protected $drupalPath;
  private $username;
  private $password;
  private $hostname;
  private $filename;
  private $jail;
  protected $config;
  protected $inputMatrix;

  /**
   * AtworkIdirUpdateController constructor.
   */
  protected function __construct() {
    $this->config = \Drupal::config('atwork_idir_update.atworkidirupdateadminsettings');
    // Use timestamp and drupalPath mainly for files
    // (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    $this->drupalPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/';
    // FTP credentials.
    $this->username = $this->config->get("idir_login_name");
    $this->password = $this->config->get("idir_login_password");
    $this->hostname = $this->config->get("idir_ftp_location");
    $this->filename = $this->config->get("idir_filename");
    $this->jail = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/';
    $this->port = 21;
  }

  /**
   * Main function that directs to other classes.
   *
   * @return array
   *   markup message that tells us this is complete.
   *
   * @throws \exception
   *   The exceptions are handled via logs.
   */
  public function main() {
    set_error_handler(array($this, 'exceptionErrorHandler'));
    $interval = 2;
    $next_execution = \Drupal::state()->get('atwork_idir_update.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : 0;
    if (REQUEST_TIME >= $next_execution) {
      // Secondary way to run the idir script for testing
      // - or if cron hook does not fire or errors for some reason.
      \Drupal::logger('AtworkIdirUpdate')->info('Running the update script');
      // Set time we ran this -
      // and don't let us run it for at least 2 minutes to avoid running twice.
      \Drupal::state()->set('atwork_idir_update.next_execution', REQUEST_TIME + $interval);
      // This contains FTP functions.
      $run_cron = $this->atworkIdirInit();
      // Now we need to set up our arrays
      // to match the idir columns to user fields.
      $current_input_matrix = new AtworkIdirUpdateInputMatrix();
      $this->inputMatrix = $current_input_matrix->getInputMatrix();
      $split_list = $this->splitList();
      \Drupal::logger('AtworkIdirUpdate')->info('Idir update ran successfully');
    }
    else {
      \Drupal::logger('AtworkIdirUpdate')->warning('Idir script was run less than two minutes ago - please check if it is still running, or wait 2 minutes before trying again');
      return [
        '#type' => 'markup',
        '#markup' => t("Idir script was run less than two minutes ago, please check if it is still running before attempting to run it again."),
      ];
    }
    isset($run_cron)?:$run_cron = "Could not download ftp";
    isset($split_list)?:$split_list = "Could not divide list";
    return array(
      '#type' => 'markup',
      '#markup' => '<p>Cron run status: <h1>' . $run_cron . '.</h1></p>',
    );
  }

  /**
   * Initiates idir fetch && prepares to parse list.
   *
   * @return string
   *   returns a note if successful for logs
   *
   * @throws \exception
   *   Throws custom exception for the logs if there is a failure.
   */
  public function atworkIdirInit() {
    // Need to pull down the file and put it in a dir.
    // Connection: Directory Sync FTP Server.
    $idir_ftp = new AtworkIdirUpdateFTP($this->jail, $this->username, $this->password, $this->hostname, $this->port);
    try {
      // Check if we can connect.
      $ftp_result = $idir_ftp->connect();
      if (!$ftp_result) {
        throw new \exception("Failed to connect to ftps");
      }
    }
    catch (FileTransferException $e) {
      \Drupal::logger('atwork_idir_update')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
    }
    catch (Exception $e) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('atwork_idir_update')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      $this->sendNotifications();
    }
    // Make dir function.
    $directory = $idir_ftp->create_idir_dir($this->timestamp);
    if ($directory == FALSE) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('atwork_idir_update')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      $this->sendNotifications();
      throw new \exception("error creating directory in public folder");
    }
    AtworkIdirLog::success("New directory created at Public://idir/" . $this->timestamp);

    // Check if file is there.
    $check_file = $idir_ftp->isFile($this->filename);
    if ($check_file == FALSE) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('atwork_idir_update')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      $this->sendNotifications();
      throw new \exception("Cannot find idir file at remote server");
    }
    AtworkIdirLog::success("Remote file " . $this->filename . " found");
    // Get the file.
    $new_idir_file = $idir_ftp->ftpFile($this->timestamp, $this->filename, $idir_ftp->connection);
    if ($new_idir_file == FALSE) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('atwork_idir_update')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      $this->sendNotifications();
      throw new \exception("Error retrieving idir file from source.");
    }
    AtworkIdirLog::success("Copied the file to drupal public folder");
    return 'Copied the file to drupal public folder';
  }

  /**
   * Log handling for splitting and parsing the different resultant lists.
   *
   * @return string
   *   Log message that denotes success.
   *
   * @throws \exception
   *   Catch and log any errors.
   */
  protected function splitList() {
    // Set up the logs.
    $split_status = $this->splitIdirLogs();
    // Unless we mark this as success, send logs and exit script.
    $split_status == 'success' ? AtworkIdirLog::success('Logs were successfully split') : $this->sendNotifications();
    AtworkIdirLog::success('Beginning to delete old idirs.');

    // File has been split, so now it is time to parse the .tsv files.
    // First run the delete script.
    $delete_status = $this->parseFiles('delete');
    $delete_status == "success" ? AtworkIdirLog::success('The delete script finished successfully') : AtworkIdirLog::errorCollect(t("Error was experienced while deleting user, see logs for more details"));
    // Second is update.
    AtworkIdirLog::success('Beginning to update current Idirs');
    $update_status = $this->parseFiles('update');
    $update_status == "success" ? AtworkIdirLog::success('The update script finished successfully') : $this->sendNotifications();
    // Finally is Add.
    AtworkIdirLog::success('Beginning to add new Idirs');
    $update_status = $this->parseFiles('add');
    $update_status == "success" ? AtworkIdirLog::success('The add script finished successfully') : $this->sendNotifications();
    AtworkIdirLog::success("All Idir updates finished successfully");
    // TODO: Cleanup function.
    // Finally send notifications.
    $this->sendNotifications();
    return "Cron ran successfully";
  }

  /**
   * Function to break out record types.
   *
   * @return string
   *   Return log value.
   *
   * @throws \exception
   *   Stop program and log if we run into issues.
   */
  private function splitIdirLogs() {
    $file_handle = new AtworkIdirUpdateLogSplit();
    $filename = 'idir_' . $this->timestamp . '.tsv';
    try {
      $full_list = fopen($this->drupalPath . 'idir/' . $this->timestamp . '/' . $filename, 'rb');
      // Check if the file was opened properly.
      if (!isset($full_list)) {
        throw new \exception("Failed to open file at Public://idir/" . $filename . '. Script was terminated in Controller.');
      }
    }
    catch (Exception $e) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      return 'fail';
    }
    // Found a file, so make sure this is closed:
    if ($full_list) {
      fclose($full_list);
    }

    // If we get here - we have a file to split - so lets move on.
    try {
      $check = $file_handle->splitFile();
      if (!$check) {
        throw new \exception("Error splitting up the user .tsv");
      }
    }
    catch (Exception $e) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      return 'fail';
    }
    unset($file_handle);
    return 'success';
  }

  /**
   * Function that directs parsing of files.
   *
   * @param string $type
   *   The type of file we will parse Delete, Add, Modify.
   *
   * @return string
   *   Return string logging success if we don't hit an exception.
   *
   * @throws \exception
   *   Capture any errors and send to logging.
   */
  private function parseFiles($type) {

    $current_list = $type == 'delete' ? new AtworkIdirDelete() : new AtworkIdirAddUpdate();
    $filename = 'idir_' . $this->timestamp . '_' . $type . '.tsv';

    try {
      $file_path = $this->drupalPath . 'idir/' . $this->timestamp . '/' . $filename;
      $full_list = fopen($file_path, 'r');
      // Check if the file was opened properly.
      if (!$full_list) {
        throw new \exception("Failed to open file at Public://idir/" . $this->timestamp . '/' . $filename . '. Script was terminated in Controller.');
      }
    }
    catch (Exception $e) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      return 'failed';
    }

    // If we get here - we have a file to parse - so lets move on.
    try {
      if ($type == 'delete') {
        $check = $current_list->deleteInit();
      }
      elseif ($type == 'add') {
        $check = $current_list->initAddUpdate("add");
      }
      elseif ($type == 'update') {
        $check = $current_list->initAddUpdate("update");
      }
      if (!$check) {
        throw new \exception("Error parsing the " . $filename . ", or no file present");
      }
    }
    catch (Exception $e) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      return 'failed';
    }
    unset($current_list);
    return 'success';
  }

  /**
   * Function that fires the function sending notifications via email.
   */
  private function sendNotifications() {
    AtworkIdirLog::notify();
  }

  /**
   * Logging function, sending messages to WS.
   *
   * @param string $severity
   *   Severity denotation for watchdog.
   * @param string $message
   *   Error message.
   * @param string $file
   *   Filename, if required.
   * @param string $line
   *   Line error occurs on.
   *
   * @throws \exception
   */
  public static function exceptionErrorHandler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
      // This error code is not included in error_reporting.
      return;
    }
    AtworkIdirLog::errorCollect($message . " " . $severity . " " . $file . " " . $line . "\n");
    throw new \exception($message . " " . $severity . " " . $file . " " . $line);
  }

}
