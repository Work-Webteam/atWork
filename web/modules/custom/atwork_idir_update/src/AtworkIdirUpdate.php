<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\Core\Entity\EntityManagerInterface;

class AtworkIdirUpdate /* implements iAtworkIdirUpdate */ 
{
  protected $full_tsv;
  protected $update_tsv;
  protected $delete_tsv;
  protected $add_tsv;

/**
   * splitFile : Responsible for turning our .tsv file download into 3 separate .tsv files, at this level we split them simply by keywords in .tsv. These .tsv files are then saved seperatly for future use. NOTE: This does not delete the .tsv file - as we would need it if we decided to rerun script.
   *
   * @param [array] $update_file - an array of the .tsv file we have pulled from the ftp site
   * @return [boolean] $file_split - allows us to know if our files saved properly, or if we have an error.
   * 
   */
  static public function splitFile()
  {
    // Check to see if we can grab the latest file, if not, send a notification and end script.
    $full_tsv = getFiles();
    // TODO: Wherever this is fired from, if it is empty, we should send Notify.
    // Nothing to do here, so send back three empty arrays.
    if(!$full_tsv)
    {
      return false;
    };  
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
    $time_stamp = date('Ymd');
    $filename = 'idir_' . $time_stamp . '.tsv';
    $drupal_path = drupal_get_path('module', 'atwork_idir_update');
    try
    {
      $full_list_check = $drupal_path . '/idir/' . $filename;
      if(!file_exists($full_list_check))
      {
        throw new Exception("Full list file not found at atwork_idir_update/idir/" . $filename );
        $full_list = fopen($drupal_path . '/idir/' . $filename, 'rb');
        if( !$full_list )
        {
          throw new Exception("Failed to open file at atwork_idir_update/idir/" . $filename );
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
    catch( Exception $e) 
    {
      // This lets us knof if hte file was missing or is broken.
      error_Collect($e);
      return false;
    }
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
  static public function parseDeleteUserList()
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
  static public function parseUpdateUserList()
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
  static public function parseAddUserList()
  {

  }


  /**
   * addUser :  This funciton takes care of adding brand new users - we know they are not in the system, so lets go ahead and add them
   *
   * @param [array] $user_to_add
   * @param [object] $userForSystem
   * @return void
   */
  static public function addUser($user_to_add)
  {

  }


  /**
   * checkUser
   *
   * @param [string] $guid : The guid of the user, run a check to see if they exist in our system or not. Then return true or false
   * @return [boolean]
   */
  static public function checkUser($guid)
  {

  }

  /**
   * checkUserFields : A function to check if the new user matches the database user - or if we need to update fields. Returns true or false
   *
   * @param IdirUserUpdate $user_to_check
   * @return [boolean]
   */
  static public function checkUserFields(IdirUserUpdate $user_to_check)
  {

  }


  /**
   * updateSystemUser: This function makes any necessary updates to our db for the user. By the time the user gets here, we know we need to make some changes. Shared by add/delete and update methods. Once successful, send a note to the success method and return
   *
   * @param IdirUserUpdate $update - The user object that needs to be updated in our system. 
   * @return void
   */
  static public function updateSystemUser(IdirUserUpdate $update)
  {

  }



  /**
   * errorCollect
   * @param : $error any errors that are caught will be forwarded here to be added to the error log (dated)
   * @return void
   */
  static public function errorCollect($error)
  {
    echo("Error " . $error);
  }
  
  /**
   * success
   *
   * @param [string] $complete : a string we will send when an update is completed successfully
   * @return void
   */
  static public function success($complete)
  {

  }

  /**
   * notify: Final function called, simply grabs the current error and success logs and emails them to the site admin - then ends the program
   *
   * @return void
   */
  static public function notify()
  {
    // Collect status and send to admin. Collect errors and send to Admin. 
  }
}