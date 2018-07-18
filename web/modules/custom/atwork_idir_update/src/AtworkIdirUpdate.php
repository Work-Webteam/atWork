<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

class AtworkIdirAddUpdate extends AtworkIdirGUID 
{
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
    $update_list = fopen($this->drupal_path . '/idir/idir_' . $this->timestamp . '_update.tsv', 'r');
    // Check if we have anything, if not throw an error.
    if( !$update_list )
    {
      // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exeption)
      throw new \exception("Failed to open file at atwork_idir_update/idir/idir_" . $this->timestamp . '_update.tsv' );
      return;
    }
    // Pull the update list
    while ( ($row = fgetcsv($update_list, '', "\t")) !== false) 
    {
      // Get the GUID of the first user, this will return either an empty set or a user entity number.
      $update_uid = $this->getGUIDField( $row[1] );
      // If we are returned an empty set, we know this user is not in our current db, and does not need to be deleted.
      if (empty($update_uid))
      {
        // If we didn't get a uid back, then this user does not exist in our system. We need to add them to the add script to run later.
        $add_class = new ATworkIdirUpdateLogSplit;
        $add_class->setAddTsv( $row );
        // Make sure the destructor breaks down the class before the next iteration.
        unset($add_class);
        continue;
      }
      // Set the fields to update the new user with
      $this->new_fields =
      [
        1 => $row[1], // GUID
        2 => $row[2], //username
        3 => $row[3], //displayname
        4 => $row[4], //email
        5 => $row[5], //givenname
        6 => $row[6], //Surname
        7 => $row[7], //Phone
        8 => $row[8], //Title
        9 => $row[9], //Department
        10 => $row[10], //Office
        11 => $row[11], //OrganizationCode
        12 => $row[12], //Company
        13 => $row[13], //Street
        14 => $row[14], //City
        15 => $row[15], //Province
        16 => $row[16], //Postal Code
      ];
      // At this point, we know they are in our system, and should be deleted.
      $result = $this -> updateSystemUser('update', $update_uid, $this->new_fields);
      // Log this transaction
      if($result)
      {
        AtworkIdirLog::success($result);
      } 
      else
      {
        AtworkIdirLog::errorCollect($result);
      }
    }
  }
}