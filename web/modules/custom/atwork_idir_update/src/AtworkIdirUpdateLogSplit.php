<?php

namespace Drupal\atwork_idir_update;

use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 * Class AtworkIdirUpdateLogSplit.
 *
 * @package Drupal\atwork_idir_update
 */
class AtworkIdirUpdateLogSplit {
  protected $timestamp;
  protected $drupalPath;
  protected $config;
  protected $inputMatrix;

  /**
   * AtworkIdirUpdateLogSplit constructor.
   */
  public function __construct() {
    $this->inputMatrix = $this->setInputMatrix();
    // Use timestamp and drupal_path mainly for files
    // (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    $this->drupalPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/';
    // Create add/update/delete .tsv files in idir folder
    // ready to be appended too - so we don't have to check every time for them.
    // If it already exists, remove it.
    if (file_exists($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_add.tsv')) {
      unlink($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_add.tsv');
    }
    $add_file = fopen($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_add.tsv', 'w');
    fclose($add_file);

    if (file_exists($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_update.tsv')) {
      unlink($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_update.tsv');
    }
    $update_file = fopen($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_update.tsv', 'w');
    fclose($update_file);

    if (file_exists($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv')) {
      unlink($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv');
    }
    $delete_file = fopen($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv', 'w');
    fclose($delete_file);
  }

  /**
   * Getter for timestamp.
   *
   * @return false|string
   *   Return the timestamp, or false if it is not available.
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * Returns the Drupal path.
   *
   * @return string
   *   The current path for Drupal.
   */
  public function getDrupalPath() {
    return $this->drupalPath;
  }

  /**
   * Setter for mapping tsv to fields array.
   *
   * @return array
   *   Array that maps .tsv to fields as per config page.
   */
  public function setInputMatrix() {
    // We have a builder class for this -
    // should only be run after we have downloaded the current idir file.
    $current_matrix = new AtworkIdirUpdateInputMatrix();
    return $current_matrix->getInputMatrix();
  }

  /**
   * Setters for the object. These will write the $user to the appropriate file.
   *
   * @param array $new_user
   *   User we need to add to the .tsv file.
   *
   * @return bool
   *   Send a bool to notify of success/failure.
   *
   * @throws \exception
   *   Log any issues.
   */
  protected function setAddTsv(array $new_user) {
    $add_file = fopen($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_add.tsv', 'a');
    if (!$add_file) {
      throw new \exception("Something has gone wrong, a user could not be added to the idir_" . $this->timestamp . "_add.tsv file");
    }

    // Make sure we have username,
    // and GUID = or else we cannot update the fields,
    // and will ignore this user.
    if (empty($new_user[$this->inputMatrix['name']]) || empty($new_user[$this->inputMatrix['field_user_guid']])) {
      \Drupal::logger('atwork_idir_update')->info("Line 64: User did not have one of the following required fields - username {$new_user[$this->inputMatrix['name']]}, guid {$new_user[$this->inputMatrix['field_user_guid']]} \n ");
      fclose($add_file);
      return TRUE;
    }
    // Put this array in .tsv form.
    fputcsv($add_file, $new_user, "\t");
    fclose($add_file);
    return TRUE;
  }

  /**
   * Add user to Delete file.
   *
   * @param array $old_user
   *   Tab delimited string with user that should be deleted.
   *
   * @return bool
   *   Returns the success/fail of this operation.
   *
   * @throws \exception
   *   Stop operation and log error.
   */
  protected function setDeleteTsv(array $old_user) {
    $delete_file = fopen($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv', 'a');
    if (!$delete_file) {
      throw new \exception("Something has gone wrong, a user could not be added to the idir_" . $this->timestamp . "_delete.tsv file");
    }
    fputcsv($delete_file, $old_user, "\t");
    fclose($delete_file);
    return TRUE;
  }

  /**
   * Add user to the Modify/update user .tsv.
   *
   * @param array $existing_user
   *   Array of user info to add to .tsv.
   *
   * @return bool
   *   Return status of operation.
   *
   * @throws \exception
   *   Stops operation, logs error.
   */
  protected function setUpdateTsv(array $existing_user) {
    $update_file = fopen($this->drupalPath . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_update.tsv', 'a');
    if (!$update_file) {
      throw new \exception("Something has gone wrong, a user could not be added to the idir_" . $this->timestamp . "_update.tsv file");
    }

    // Make sure we have a GUID and username or we will ignore this user.
    if (empty($existing_user[$this->inputMatrix['name']]) || empty($existing_user[$this->inputMatrix['field_user_guid']])) {
      \Drupal::logger('atwork_idir_update')->info("Line 98: User did not have one of the following required fields - username {$existing_user[$this->inputMatrix['name']]}, guid {$existing_user[$this->inputMatrix['field_user_guid']]}");
      fclose($update_file);
      return TRUE;
    }
    fputcsv($update_file, $existing_user, "\t");
    fclose($update_file);
    return TRUE;
  }

  /**
   * Responsible for turning our .tsv file download into 3 separate .tsv files.
   *
   * We split the idir.tsv by keywords in .tsv.
   * These .tsv files are then saved separately for future use.
   * NOTE: This does not delete the .tsv file -
   * as we would need it if we decided to rerun script.
   *
   * @return bool
   *   Success or failure of the operation.
   *
   * @throws \exception
   *   Stops operation and logs error.
   */
  public function splitFile() {
    // Check to see if we can grab the latest file,
    // if not, send a notification and end script.
    $file_split = NULL;
    $file_split = $this->getFiles();
    // Nothing to do here, so send back three empty arrays.
    if ($file_split != TRUE) {
      throw new \exception("Something has gone wrong, some or all of the update .tsv files were not parsed.");
    }
    else {
      return TRUE;
    }
  }

  /**
   * Check if we have the idir file, call setters on lists.
   *
   * @return bool
   *   Returns result of checking if file exists.
   *
   * @throws \exception
   *   Halt operation and log error.
   */
  private function getFiles() {
    $filename = 'idir_' . $this->timestamp . '.tsv';
    $check = TRUE;
    try {
      // Check to see that the file is where it should be.
      $full_list = fopen($this->drupalPath . '/idir/' . $this->timestamp . '/' . $filename, 'rb');
      // Check if the file was opened properly.
      if (!$full_list) {
        throw new \exception("Failed to open file at Public://idir/" . $this->timestamp . '/' . $filename);
      }
      else {
        // We have a file, and need to identify
        // which field holds the "Action" value (set in Admin settings)".
        while (($row = fgetcsv($full_list, '', "\t")) !== FALSE) {
          // We don't need headers, skip first line.
          // TODO: Make this more extensible - this may not carry every case.
          if ($row[0] == 'TransactionType') {
            continue;
          }

          // Put it in an array.
          switch (TRUE) {
            // Everything marked as add.
            case($row[$this->inputMatrix['action']] == "Add"):
              // Check is a boolean, set to tell us if the record
              // was updated (will return true) or not (will return false).
              // This may be useful for error -checking,
              // or rebooting script if necessary.
              $check = $this->setAddTsv($row);
              break;

            case($row[$this->inputMatrix['action']] == "Modify"):
              $check = $this->setUpdateTsv($row);
              break;

            case($row[$this->inputMatrix['action']] == "Delete"):
              $check = $this->setDeleteTsv($row);
              break;
          }
        }
      }
    }
    catch (FileNotFoundException $e) {
      // This lets us know if hte file was missing or is broken.
      AtworkIdirLog::errorCollect($e);
      return FALSE;
    }
    catch (FileNotOpenedException $e) {
      // This lets us know if the file was missing or is broken.
      AtworkIdirLog::errorCollect($e);
      return FALSE;
    }
    catch (Exception $e) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well.
      AtworkIdirLog::errorCollect($e);
      return FALSE;
    }
    return $check;
  }

  /**
   * Getter for the modules path.
   *
   * @param string $moduleName
   *   Name of the module we require the path to.
   *
   * @return string
   *   The path location of the module.
   */
  protected function getModulePath($moduleName) {
    return drupal_get_path('module', $moduleName);
  }

}
