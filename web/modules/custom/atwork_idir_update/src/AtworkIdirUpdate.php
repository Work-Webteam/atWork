<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;

class AtworkIdirUpdate /* implements iAtworkIdirUpdate */ 
{
  protected $full_tsv;
  protected $update_tsv;
  protected $delete_tsv;
  protected $add_tsv;
  protected $timestamp;
  protected $drupal_path;
  
  function __construct(){
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
   private function getFiles(){
    $filename = 'idir_' . $this->timestamp . '.tsv';
    // Use this for global function drupal_get_module() so that we can implement unit tests.
   
    try
    {
      // Check to see that the file is where it should be
      $full_list_check = $this->drupal_path . '/idir/' . $filename;
      if(!file_exists($full_list_check))
      {
        // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
        throw new \exception("Full list file not found at atwork_idir_update/idir/" . $filename );
        // Grab the file and open it for reading
        $full_list = fopen($drupal_path . '/idir/' . $filename, 'rb');
        // Check if the file was opened properly.
        if( !$full_list )
        {
          // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
          throw new \exception("Failed to open file at atwork_idir_update/idir/" . $filename );
        }
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
              set_add_tsv( $row );
              break;
            case($row[0] == "Modify") :
              set_update_tsv( $row );
              break;
            case($row[0] == "Delete") :
              set_delete_tsv( $row );
              break;
          }
        return true;
        }
      }
    } 
    catch( FileNotFoundException $e) 
    {
      // This lets us know if hte file was missing or is broken.
      error_Collect($e);
      return false;
    }
    catch( FileNotOpenedException $e)
    {
      // This lets us know if the file was missing or is broken.
      error_Collect($e);
      return false;
    }
    catch (Exception $e) {
      // Generic exception handling if something else gets thrown.
      \Drupal::logger('AtworkIdirUpdate')->error($e->getMessage());
    }
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
   * addUser :  This funciton takes care of adding brand new users - we know they are not in the system, so lets go ahead and add them
   *
   * @param [array] $user_to_add
   * @param [object] $userForSystem
   * @return void
   */
  private function addUser($user_to_add)
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

  }

  /**
   * checkUserFields : A function to check if the new user matches the database user - or if we need to update fields. Returns true or false
   *
   * @param IdirUserUpdate $user_to_check
   * @return [boolean]
   */
  private function checkUserFields(IdirUserUpdate $user_to_check)
  {

  }


  /**
   * updateSystemUser: This function makes any necessary updates to our db for the user. By the time the user gets here, we know we need to make some changes. Shared by add/delete and update methods. Once successful, send a note to the success method and return
   *
   * @param IdirUserUpdate $update - The user object that needs to be updated in our system. 
   * @return void
   */
  private function updateSystemUser(IdirUserUpdate $update)
  {

  }



  /**
   * errorCollect
   * @param : $error any errors that are caught will be forwarded here to be added to the error log (dated)
   * @return void
   */
  private function errorCollect($error)
  {
    echo("Error " . $error);
  }
  
  /**
   * success
   *
   * @param [string] $complete : a string we will send when an update is completed successfully
   * @return void
   */
  public function success($complete)
  {

  }

  /**
   * notify: Final function called, simply grabs the current error and success logs and emails them to the site admin - then ends the program
   *
   * @return void
   */
  public function notify()
  {
    // Collect status and send to admin. Collect errors and send to Admin. 
  }
}