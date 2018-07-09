<?php 

namespace Drupal\atwork_idir_update;
use \Database\Core\Database\Database;


interface IdirUserUpdate
{
  /**
   * splitFile : Responsible for turning our .tsv file download into 3 separate .tsv files, at this level we split them simply by keywords in .tsv. These .tsv files are then saved seperatly for future use. NOTE: This does not delete the .tsv file - as we would need it if we decided to rerun script.
   *
   * @param [array] $update_file - an array of the .tsv file we have pulled from the ftp site
   * @return null
   * 
   */
  public function splitFile($update_file); 
  
  

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
  public function parseDeleteUserList();

  /**
   * parseUpdateUserList - This function pulls users one at a time from the update.tsv, and then completes a check on them
   * 
   * @param [array] $active_user_check: An array of info for the user we are currently checking from the update.tsv file
   * @param [string] $guid : The guid of the user we pulled off of the .tsv sheet
   * @param [boolean] $is_active : Checks with the check function to see if the user is currently in our system. If not we need to add them, if yes we can check the fields to determine if they need to be updated.
   * @return void
   */
  public function parseUpdateUserList();

  /**
   * parseAddUserList() : parses the add.tsv
   * 
   * @param [array] $active_user_check :  An array of info for the user we are currently checking from the update.tsv file
   * @param [string] $guid : The guid of the user we pulled from the .tsv sheet - sent to the check function
   * @param [boolean] $is_active : Checks to see if the user is in our system already. If they are, then send to check fields to see if anything has changed. If they are not in our system already, send them ($active_user_check) to addUser to build a user object
   *  @return void
   */
  public function parseAddUserList();


  /**
   * addUser :  This funciton takes care of adding brand new users - we know they are not in the system, so lets go ahead and add them
   *
   * @param [array] $user_to_add
   * @param [object] $userForSystem
   * @return void
   */
  public function addUser($user_to_add);


  /**
   * checkUser
   *
   * @param [string] $guid : The guid of the user, run a check to see if they exist in our system or not. Then return true or false
   * @return [boolean]
   */
  public function checkUser($guid);

  /**
   * checkUserFields : A function to check if the new user matches the database user - or if we need to update fields. Returns true or false
   *
   * @param IdirUserUpdate $user_to_check
   * @return [boolean]
   */
  public function checkUserFields(IdirUserUpdate $user_to_check);


  /**
   * updateSystemUser: This function makes any necessary updates to our db for the user. By the time the user gets here, we know we need to make some changes. Shared by add/delete and update methods. Once successful, send a note to the success method and return
   *
   * @param IdirUserUpdate $update - The user object that needs to be updated in our system. 
   * @return void
   */
  public function updateSystemUser(IdirUserUpdate $update);



  /**
   * errorCollect
   * @param : $error any errors that are caught will be forwarded here to be added to the error log (dated)
   * @return void
   */
  public function errorCollect($error);
  
  /**
   * success
   *
   * @param [string] $complete : a string we will send when an update is completed successfully
   * @return void
   */
  public function success($complete);

  /**
   * notify: Final function called, simply grabs the current error and success logs and emails them to the site admin - then ends the program
   *
   * @return void
   */
  public function notify();




}

