<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

class AtworkIdirUpdate /* implements iAtworkIdirUpdate */ 
{
  protected $full_tsv;
  protected $update_tsv;
  protected $delete_tsv;
  protected $add_tsv;
  protected $timestamp;
  protected $drupal_path;
  
  function __construct()
  {
    // Use timestamp and drupal_path mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    // TODO: Should these be going into the Public:// file folder?
    $this->drupal_path = $this->getModulePath('atwork_idir_update');
    // Create possible add/update/delete .tsv files in idir folder ready to be appended too - so we don't have to check every time for them.
    $add_file = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_add.tsv', 'w');
    fclose($add_file);
    $update_file = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_update.tsv', 'w');
    fclose($update_file);
    $delete_file = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_delete.tsv', 'w');
    fclose($delete_file);
  }
  
  /**
  * Setters for the object. These will write the $user to the appropriate file.
  */
  protected function setAddTsv($new_user)
  {
    $add_file = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_add.tsv', 'a');
    if(!$add_file)
    {
      throw new \exception("Something has gone wrong, a user could not be added to the idir_" . $this->timestamp . "_add.tsv file");
      return false;
    }
    // Put this array in .tsv form.
    fputcsv($add_file, $new_user,"\t");
    fclose($add_file);
    return true;
  }
  protected function setDeleteTsv($old_user)
  {
    $delete_file = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_delete.tsv', 'a');
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
    $update_file = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_update.tsv', 'a');
    if(!$update_file)
    {
      throw new \exception("Something has gone wrong, a user could not be added to the idir_" . $this->timestamp . "_update.tsv file");
      return false;
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
    if(!$file_split)
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
    $check = false;
    try
    {
      // Check to see that the file is where it should be
      $full_list_check = $this->drupal_path . '/idir/' . $filename;
      if(!file_exists($full_list_check))
      {
        // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
        throw new \exception("Full list file not found at atwork_idir_update/idir/" . $filename );
        return;
      }
      else {
        // Grab the file and open it for reading
        $full_list = fopen($this->drupal_path . '/idir/' . $filename, 'rb');
      }
      // Check if the file was opened properly.
      if( !$full_list )
      {
        // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
        throw new \exception("Failed to open file at atwork_idir_update/idir/" . $filename );
        return;
      } 
      else 
      {
        while ( ($row = fgetcsv($full_list, '', "\t")) !== false) {
          // we don't need headers now
          if($row[0] == 'TransactionType'){
            continue;
          }
          // put it in an array
          switch(true)
          {
            // Everything marked as add
            case($row[0] == "Add") :
              // Check is a boolean, set to tell us if the record was updated (will return true) or not (will return false). This may be useful for error -checking, or rebooting script if necessary.
              $check = $this->setAddTsv( $row );
              break;
            case($row[0] == "Modify") :
              $check = $this->setUpdateTsv( $row );
              break;
            case($row[0] == "Delete") :
              $check = $this->setDeleteTsv( $row );
              break;
          }
        }
      }
    } 
    catch( FileNotFoundException $e) 
    {
      // This lets us know if hte file was missing or is broken.
      AtworkIdirLogs::errorCollect($e);
      return false;
    }
    catch( FileNotOpenedException $e)
    {
      // This lets us know if the file was missing or is broken.
      AtworkIdirLogs::errorCollect($e);
      return false;
    }
    catch (Exception $e) 
    {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
      AtworkIdirLogs::errorCollect($e);
    }
    return $check;
  }

  protected function getModulePath($moduleName)
  {
    return drupal_get_path('module', $moduleName);
  }

  /**
   * parseDeleteUserList - This function does the work of parsing the delete.tsv file
   * @param[array] $active_user_check : An array for the user we are currently checking in the delete.tsv file
   * @param [string] $guid : The guid of the user we pull off of the .tsv sheet
   * @param [boolean] $is_active : Checks with the check function to see if the user is currently in our system. If not we can move on to the next one.
   * @param [boolean] $user_available : Checks if user WAS in teh system, but had been deactivated already. If already deactivated, no further action required
   * @param [object] $user_update : If the user is in our system, grab them and update/deactivate all pertinent fields. Then send this user to the Update method
   * @param [string] $status : Will send error to error method, or success to success method.
   * @return void
   */
  private function parseDeleteUserList()
  {
    // Grab the list of users to be deleted
    $delete_list = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_delete.tsv', 'r');
    // Check if we have anything, if not throw an error.
    if( !$delete_list )
    {
      // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
      throw new \exception("Failed to open file at atwork_idir_update/idir/idir_" . $this->timestamp . '_delete.tsv' );
      return;
    }
    // Pull the delete list
    while ( ($row = fgetcsv($delete_list, '', "\t")) !== false) {
      // Get the GUID of the first user, this will return either an empty set or a user entity number.
      $delete_uid = $this->check_user = $row[1];
      // If we are returned an empty set, we know this user is not in our current db, and does not need to be deleted.
      if (empty($delete_uid))
      {
        continue;
      }
      // At this point, we know they are in our system, and should be deleted.
      // updateSystemUser($type, $uid, $fields)
      // Todo: Create our own array with removed info and send them to user-update?
      $new_fields = 
      [
        1 => 'old_guid_' . time(),
        2 => 'old_user_' . time(),
        4 => 'old_user_' . time() . '@gov.bc.ca',
        5 => '',
        6 => '',
        7 => '',
        8 => '',
        9 => '',
        10 => '',
        11 => '',
        12 => ''
      ];
      $result = $this -> updateSystemUser('delete', $delete_uid, $new_fields);
      // Log this transaction
      if($result == true){
        AtworkIdirLogs::success($result);
      } 
      else
      {
        AtworkIdirLogs::errorCollect($result);
      }
    }
    
    // Check if this user is in our system already
    // If not, we are done, move onto the next user
    // If so, we need to send this to delete user.
  }

  /**
   * parseUpdateUserList - This function pulls users one at a time from the update.tsv, and then completes a check on them
   * 
   * @param [array] $active_user_check: An array of info for the user we are currently checking from the update.tsv file
   * @param [string] $guid : The guid of the user we pulled off of the .tsv sheet
   * @param [boolean] $is_active : Checks with the check function to see if the user is currently in our system. If not we need to add them, if yes we can check the fields to determine if they need to be updated.
   * @return void
   */
  private function parseUpdateUserList()
  {

  }

  /**
   * parseAddUserList() : parses the add.tsv
   * 
   * @param [array] $active_user_check :  An array of info for the user we are currently checking from the update.tsv file
   * @param [string] $guid : The guid of the user we pulled from the .tsv sheet - sent to the check function
   * @param [boolean] $is_active : Checks to see if the user is in our system already. If they are, then send to check fields to see if anything has changed. If they are not in our system already, send them ($active_user_check) to addUser to build a user object
   *  @return void
   */
  private function parseAddUserList()
  {

  }

  /**
   * checkUser
   *
   * @param [string] $guid : The guid of the user, run a check to see if they exist in our system or not. Then return true or false
   * @return [boolean]
   */
  private function checkUser($guid)
  {
    // Check if guid exists. If not this is a new user.
  }

  /**
   * checkUserFields : A function to check if the new user matches the database user - or if we need to update fields. Returns true or false
   *
   * @param IdirUserUpdate $user_to_check
   * @return [boolean]
   */
  private function checkUserFields(IdirUserUpdate $user_to_check)
  {
    // Check fields to see if we need to update any of them.
    $user_object = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $account = User::load(\Drupal::currentUser()->id());

  }

  /** 
   * This function takes a GUID input, then returns user number (if the user is in our system) or else false if not in our system
   */
  public function getGUIDField($guid)
  {
    //$query = \Drupal::entityQuery('user')
    // ->condition('field_guid.entity.value', $guid, '=');
    //$result = $query->execute();
    $connection = \Drupal::database();
    $result = $connection->select('user__field_guid', 'fg')
      ->fields('fg', array('entity_id'))
      ->distinct(true)
      ->condition("fg.field_guid_value", $guid, '=')
      ->execute()->fetchCol();
      //echo($result);
      return $result;
  }

  /**
   * updateSystemUser: This function makes any necessary updates to our db for the user. By the time the user gets here, we know we need to make some changes. Shared by add/delete and update methods. Once successful, send a note to the success method and return
   *
   * @param [string] $type : This denotes if this is an update or a delete
   * @param  [string] $uid : userid we found when checking for a user - can load with this
   * @param [array] $fields : An array of fields we need for the user.  
   * @return void
   */
  public function updateSystemUser($type, $uid, $fields)
  {
    // User fields are updated with new info, and user is saved.
    if($type == 'add')
    {
      $this_user = User::create();
    }
    else
    {
      $this_user = User::load($uid);
    }
    $this_user->set('init', $fields['4']);
    $this_user->setUsername($fields['2']);
    $this_user->setPassword($fields['1']);
    $this_user->setEmail($fields['4']);
    // TODO: add in other user fields here.
    $this_user->set('field_guid', $fields['1']);
    $this_user->set('field_display_name', $fields['3']);
    $this_user->set('field_given_name', $fields['5']);
    $this_user->set('field_surname', $fields['6']);
    $this_user->set('field_phone', $fields['7']);
    $this_user->set('field_title', $fields['8']);
    $this_user->set('field_department', $fields['9']);
    $this_user->set('field_office', $fields['10']);
    $this_user->set('field_organization_code', $fields['11']);
    $this_user->set('field_company', $fields['12']);
    $this_user->set('field_street', $fields['13']);
    $this_user->set('field_city', $fields['14']);
    $this_user->set('field_province', $fields['15']);
    $this_user->set('field_postal_code', $fields['16']);

    // We need this to for sure be a new user - we don't want to edit an existing user.
    if($type == 'add')
    {
      $this_user->enforceIsNew(true);
    }
    if($type ==  'delete')
    {
      // This unpublishes their account
      $this_user->block();
    }
    else
    {
      // This publishes their account.
      $this_user->activate();
    }
    // Save user
    $result = $this_user->save();

    return $result;
  }
}