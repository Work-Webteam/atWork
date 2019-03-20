<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

class AtworkIdirUpdateLogSplit 
{
  protected $timestamp;
  protected $drupal_path;
  protected $config;
  protected $input_matrix;

  function __construct()
  {
    $this->input_matrix = $this->setInputMatrix();
    // Use timestamp and drupal_path mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    // TODO: Should these be going into the Public:// file folder?
    $this->drupal_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/';
    // Create possible add/update/delete .tsv files in idir folder ready to be appended too - so we don't have to check every time for them.
    if(file_exists($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_add.tsv'))
    {
      unlink($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_add.tsv');
    }  
    $add_file = fopen($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_add.tsv', 'w');
    fclose($add_file);

    if(file_exists($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_update.tsv'))
    {
      unlink($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_update.tsv');
    }  
    $update_file = fopen($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_update.tsv', 'w');
    fclose($update_file);

    if(file_exists($this->drupal_path . '/idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv'))
    {
      unlink($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv');
    }
    $delete_file = fopen($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv', 'w');
    fclose($delete_file);
  }
  public function getTimestamp(){
    return $this->timestamp;
  }
  public function getDrupalPath(){
    return $this->drupal_path;
  }
  public function setInputMatrix(){
    // We have a builder class for this - should only be run after we have downloaded the current idir file
    $current_matrix = new AtworkIdirUpdateInputMatrix();
    return $current_matrix->getInputMatrix();
  }
    
  /**
  * Setters for the object. These will write the $user to the appropriate file.
  */
  protected function setAddTsv($new_user)
  {
    $add_file = fopen($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_add.tsv', 'a');
    if(!$add_file)
    {
      throw new \exception("Something has gone wrong, a user could not be added to the idir_" . $this->timestamp . "_add.tsv file");
      return false;
    }

    // Make sure we have an email and username, and GUID = or else we cannot update the fields, and will ignore this user.
    if(empty($new_user[$this->input_matrix['mail']]) || empty($new_user[$this->input_matrix['name']]) || empty($new_user[$this->input_matrix['field_user_guid']]))
    {
      \Drupal::logger('atwork_idir_update')->info("Line 64: User did not have one of the following required fields - email {$new_user[$this->input_matrix['mail']]}, username {$new_user[$this->input_matrix['name']]}, guid {$new_user[$this->input_matrix['field_user_guid']]} \n ");
      fclose($add_file);
      return true;
    }
    // Put this array in .tsv form.
    fputcsv($add_file, $new_user,"\t");
    fclose($add_file);
    return true;
  }
  protected function setDeleteTsv($old_user)
  {
    $delete_file = fopen($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv', 'a');
    
    if(!$delete_file)
    {
      throw new \exception("Something has gone wrong, a user could not be added to the idir_" . $this->timestamp . "_delete.tsv file");
      return false;
    }
    fputcsv($delete_file, $old_user, "\t");
    fclose($delete_file);
    return true;
  }
  protected function setUpdateTsv($existing_user)
  {
    $update_file = fopen($this->drupal_path . '/idir/' .  $this->timestamp . '/idir_' . $this->timestamp . '_update.tsv', 'a');
    if(!$update_file)
    {
      throw new \exception("Something has gone wrong, a user could not be added to the idir_" . $this->timestamp . "_update.tsv file");
      return false;
    }

    // Make sure we have an email and username or we will ignore this user.
    if(empty($existing_user[$this->input_matrix['mail']]) || empty($existing_user[$this->input_matrix['name']]) || empty($existing_user[$this->input_matrix['field_user_guid']]))
    {
      \Drupal::logger('atwork_idir_update')->info("Line 98: User did not have one of the following required fields - email {$existing_user[$this->input_matrix['mail']]}, username {$existing_user[$this->input_matrix['name']]}, guid {$existing_user[$this->input_matrix['field_user_guid']]}");
      fclose($update_file);
      return true;
    }
    fputcsv($update_file, $existing_user, "\t");
    fclose($update_file);
    return true;
  }
    /**
   * splitFile : Responsible for turning our .tsv file download into 3 separate .tsv files, at this level we split them simply by keywords in .tsv. These .tsv files are then saved seperatly for future use. NOTE: This does not delete the .tsv file - as we would need it if we decided to rerun script.
   *
   * @param [array] $update_file - an array of the .tsv file we have pulled from the ftp site
   * @return [boolean] $file_split - allows us to know if our files saved properly, or if we have an error.
   * 
   */
  public function splitFile()
  {
    // Check to see if we can grab the latest file, if not, send a notification and end script.
    $file_split = null;
    $file_split = $this->getFiles();
    // TODO: Wherever this is fired from, if it is empty, we should send Notify.
    // Nothing to do here, so send back three empty arrays.
    if($file_split != true)
    {
      throw new \exception("Something has gone wrong, some or all of the update .tsv files were not parsed.");
      return false;
    }
    else 
    {
      return true;
    }  
  } 
  
  /**
   * This function looks for todays idir file, and splits it into three different files.
   * @param [date] $time_stamp : Timestamp with todays date, so we can identify the proper idir .tsv to pull
   * @param [string] $filename : Putting together the expected filename
   * @param [string] $drupal_path : grabbing the filepath to the idir script
   * @param [strong] $row : Current row from the tsv list
   * @return void
   */
   private function getFiles()
   {
    $filename = 'idir_' . $this->timestamp . '.tsv';
    $check = true;
    try
    {
      // Check to see that the file is where it should be
      $full_list = fopen($this->drupal_path . '/idir/' .  $this->timestamp . '/' . $filename, 'rb');

      // Check if the file was opened properly.
      if( !$full_list )
      {
        // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
        throw new \exception("Failed to open file at Public://idir/" . $this->timestamp . '/' . $filename );
        return false;
      } 
      else 
      {
        // We have a file, and need to identify which field holds the "Action" value (set in Admin settings)"
        while ( ($row = fgetcsv($full_list, '', "\t")) !== false) {
          // we don't need headers now
          if($row[0] == 'TransactionType'){
            continue;
          }

          // put it in an array
          switch(true)
          {
            // Everything marked as add
            case($row[$this->input_matrix['action']] == "Add") :
              // Check is a boolean, set to tell us if the record was updated (will return true) or not (will return false). This may be useful for error -checking, or rebooting script if necessary.
              $check = $this->setAddTsv( $row );
              break;
            case($row[$this->input_matrix['action']] == "Modify") :
              $check = $this->setUpdateTsv( $row );
              break;
            case($row[$this->input_matrix['action']] == "Delete") :
              $check = $this->setDeleteTsv( $row );
              break;
          }
        }
      }
    } 
    catch( FileNotFoundException $e) 
    {
      // This lets us know if hte file was missing or is broken.
      AtworkIdirLog::errorCollect($e);
      return false;
    }
    catch( FileNotOpenedException $e)
    {
      // This lets us know if the file was missing or is broken.
      AtworkIdirLog::errorCollect($e);
      return false;
    }
    catch (Exception $e) 
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      // And log it as well
      AtworkIdirLog::errorCollect($e);
      return $check;
    }
    return $check;
  }

  protected function getModulePath($moduleName)
  {
    return drupal_get_path('module', $moduleName);
  }
}
