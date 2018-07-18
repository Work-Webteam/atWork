<?php

namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

class AtworkIdirAdd extends AtworkIdirGUID
{

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
    $add_list = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_add.tsv', 'r');
    // Check if we have anything, if not throw an error.
    if( !$add_list )
    {
      // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
      throw new \exception("Failed to open file at atwork_idir_update/idir/idir_" . $this->timestamp . '_add.tsv' );
      return;
    }
    // Pull the add list
    while ( ($row = fgetcsv($add_list, '', "\t")) !== false) 
    {
      
    }


  }

}