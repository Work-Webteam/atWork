<?php

namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

class AtworkIdirDelete extends AtworkIdirGUID
{
  
  public function deleteInit()
  {
    $delete_status = $this->parseDeleteUserList();
    return $delete_status;
  }
  /**
   * parseDeleteUserList - This function does the work of parsing the delete.tsv file
   * @param[array] $active_user_check : An array for the user we are currently checking in the delete.tsv file
   * @param [string] $guid : The guid of the user we pull off of the .tsv sheet
   * @param [boolean] $is_active : Checks with the check function to see if the user is currently in our system. If not we can move on to the next one.
   * @param [boolean] $user_available : Checks if user WAS in teh system, but had been deactivated already. If already deactivated, no further action required
   * @param [object] $user_update : If the user is in our system, grab them and update/deactivate all pertinent fields. Then send this user to the Update method
   * @param [string] $status : Will send error to error method, or success to success method.
   * @param [string] $drupal_path : path to the module, part of the AtworkIdirGUID __constructor
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
      return false;
    }
    // TODO: Should we programatically count how many fields the user has? Then we don't have to update this everytime we add a new field?
    $this->new_fields = 
    [
      // Don't need to remove old password and don't want to remove GUID in case this user comes back, so leave 1 out.
      2 => 'old_user_' . time(),
      // We don't want to remove old display names - so leave 3 out
      4 => 'old_user_' . time() . '@gov.bc.ca',
      // Custom fields start here
      5 => '',
      6 => '',
      7 => '',
      8 => '',
      9 => '',
      10 => '',
      11 => '',
      12 => '',
      13 => '',
      15 => '',
      16 => ''
    ];
    // Pull the delete list
    while (($row = fgetcsv($delete_list, '', "\t")) !== false) 
    {
      // Get the GUID of the first user, this will return either an empty set or a user entity number.
      $delete_uid = $this->getGUIDField($row[1]);
      // If we are returned an empty set, we know this user is not in our current db, and does not need to be deleted.
      if (empty($delete_uid))
      {
        continue;
      }
      // At this point, we know they are in our system, and should be deleted.
      $result = $this -> updateSystemUser('delete', $delete_uid, $this->new_fields);
      // TODO: Log this transaction

    }
    return true;
  }
}