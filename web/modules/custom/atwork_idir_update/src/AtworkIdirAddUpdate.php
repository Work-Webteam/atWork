<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\AtworkIdirUpdateInputMatrix;
use Drupal\user\Entity\User;

class AtworkIdirAddUpdate extends AtworkIdirGUID 
{

  public function initAddUpdate($type)
  {
    $update_status = $this->parseUpdateUserList($type);
    return $update_status;
  }
  /**
   * parseUpdateUserList - This function pulls users one at a time from the update.tsv, and then completes a check on them
   * 
   * @param [array] $active_user_check: An array of info for the user we are currently checking from the update.tsv file
   * @param [string] $guid : The guid of the user we pulled off of the .tsv sheet
   * @param [boolean] $is_active : Checks with the check function to see if the user is currently in our system. If not we need to add them, if yes we can check the fields to determine if they need to be updated.
   * @param [string] $list : will state either "add" or "update" to pull the appropriate list. All other checks will decide if user is new/needs to be updated/ hits an integrety constraint (GUID or Idir).
   * @return string
   */
  protected function parseUpdateUserList($list)
  {
    $update_list = fopen($this->drupal_path . 'idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_' . $list . '.tsv', 'r');
    // Check if we have anything, if not throw an error.
    if( !isset($update_list) ) {
      // TODO: Eventually this should be updated to reflect this exact Exception (FileNotFoundException extends Exception)
      throw new \exception("Failed to open file at atwork_idir_update/idir/" . $this->timestamp . "/idir_" . $this->timestamp . '_' . $list . '.tsv');
      return "failed";
    }
    // Pull the update list
    while ( ($row = fgetcsv($update_list, '', "\t")) !== false) 
    {

      // These MUST both have a value
      if(!isset($row[$this->input_matrix['name']]) || !isset($row[$this->input_matrix['field_user_guid']])){
        continue;
      }
      // Get the GUID of the first user, this will return either an empty set or a user entity number.
      $update_uid = $this->getGUIDField( $row[$this->input_matrix['field_user_guid']] );

      // If we are returned an empty set, we know this user is not in our current db, and in fact needs to be added. We should also do a quick check for username (idir) because we can't duplicate this. If this was the user script, we can simply append them to the add script which will run last.
      if (empty($update_uid))
      {
        {
          // Need to check if idir is in user - we cannot have two users with the same idir and different GUID's
          $new_uid = $this->getUserName( $row[$this->input_matrix['name']] );

          if( !empty($new_uid) )
          {
            // setup $this->new_fields for a delete and submit
            $result = $this->removeUser( $row , $new_uid[0]);
          }
          //setup $this->new_fields for an add and submit - we don't have this guid or idir in the system.
          $result = $this->addUser($row);
        }
      } 
      // we have a uid to update that is associated with a guid
      else
      {
        //WE have a GUID - lets check that the username matches our new username. If not we need to check if this username already exists.
        // Need to check if idir is in user - we cannot have two users with the same idir and different GUID's
        $match_uid = $this->getUserName( $row[$this->input_matrix['name']] );
        if(isset($match_uid[0]) && $match_uid[0] != $update_uid[0])
        {
          // setup $this->new_fields for a delete and submit
          $result = $this->removeUser( $row , $match_uid[0] );
        }

        // Set the fields to update the new user with
        // Make sure we are starting fresh first
        unset($this->new_fields);
        // Here we need to get all userfields, and map back the values in the proper row#
        // TODO: We need to set this up so that the new field numbers point to the column numbers
        foreach($this->input_matrix as $key=>$value){
          $this->new_fields[$value] = $row[$value];
        }

        /* Don't use this anymore - but good for us to see previous mappings
        $this->new_fields =
        [
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
          16 => substr($row[16], 0, 7), //Postal Code
        ];
        */
        // At this point, we know they are in our system, and should be updated.
        $result = $this->updateSystemUser('update', $update_uid[0], $this->new_fields);
      }
      // Log this transaction
      if($result)
      {
        AtworkIdirLog::success($result);
      } 
    }
    return "success";
  }

  private function addUser( $user_array ) {
    unset($this->new_fields);
    // Here we need to get all userfields, and map back the values in the proper row#
    // TODO: We need to set this up so that the new field numbers point to the column numbers
    foreach($this->input_matrix as $key=>$value){
      $this->new_fields[$value] = $user_array[$value];
    }

    /* Not using this anymore - but useful to see the old mapping
    $this->new_fields =
    [
      1 => $user_array[1], // GUID
      2 => $user_array[2], //username
      3 => $user_array[3], //displayname
      4 => $user_array[4], //email
      5 => $user_array[5], //givenname
      6 => $user_array[6], //Surname
      7 => $user_array[7], //Phone
      8 => $user_array[8], //Title
      9 => $user_array[9], //Department
      10 => $user_array[10], //Office
      11 => $user_array[11], //OrganizationCode
      12 => $user_array[12], //Company
      13 => $user_array[13], //Street
      14 => $user_array[14], //City
      15 => $user_array[15], //Province
      16 => substr($user_array[16], 0, 7), //Postal Code - There have been instances where the file contains two PC's, which is too large for this field. We trim them to a set 7 chars here.
    ]; */
    // Calls parent function requires udpateSystemUser($type, $uid, array of fields)
    $result = $this->updateSystemUser('add', '', $this->new_fields);
    return $result;
  }
  private function removeUser( $user_array , $uid)
  {
    // We need to make sure we aren't carrying any old information in the new_fields var.
    $this->new_fields = null;
    $extra_rand = rand( 10000, 99999 );
    foreach($this->input_matrix as $key=>$value){
      if($key == "name") {
       // Replace username
        $this->new_fields[$value] = 'old_user_' . time() . $extra_rand;
      } elseif($key == "mail") {
        // Replace email
        $this->new_fields[$value] = 'old_user_' . time() . $extra_rand . '@gov.bc.ca';
      } elseif($key == "field_user_guid"){
        // Keep GUID in place, in case we re-activate this user, they will get back their old content
        $this->new_fields[$value] = $user_array[$value];

      } else {
        // We are removing all info. We don't want to overwrite name or mail with init or login name, so if it has already been set, ignore
        if(!isset($this->new_fields[$value])){
          $this->new_fields[$value] = '';
        }
      }
    }
    /* No longer use this - but lets keep it around to remember our field mappings
    $this->new_fields = 
    [
      // Don't need to remove old password and don't want to remove GUID in case this user comes back, so leave this out.
      2 => 'old_user_' . time() . $extra_rand,
      // We don't want to remove old display names - so leave 3 out
      4 => 'old_user_' . time() . $extra_rand . '@gov.bc.ca',
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
    */
    $result = $this->updateSystemUser('delete', $uid, $this->new_fields);
    return $result;
  }
  private function getUserName( $username )
  {
    $user_uid = null;
    $connection = \Drupal::database();
    $result = $connection->select('users_field_data', 'fd')
      ->fields('fd', array('uid'))
      ->distinct(true)
      ->condition('fd.name', $username, '=')
      ->execute()->fetchCol();
      return $result;
  }
}
