<?php

namespace Drupal\atwork_idir_update;

use Drupal\Database\Core\Database\Database;
use Drupal\AtworkIdirUpdateInputMatrix;
use Drupal\user\Entity\User;

/**
 * Class AtworkIdirAddUpdate.
 *
 * @package Drupal\atwork_idir_update
 */
class AtworkIdirAddUpdate extends AtworkIdirGUID {

  /**
   * Inits updates/Adds functions.
   *
   * @param string $type
   *   String denoting if this is a modify or add.
   *
   * @return string
   *   Returns a success message or errors out.
   *
   * @throws \exception
   *   Exception will stop program and submit message to watchdog.
   */
  public function initAddUpdate($type) {
    $update_status = $this->parseUpdateUserList($type);
    return $update_status;
  }

  /**
   * This function pulls users from the update.tsv, and then checks them.
   *
   * @param string $list
   *   String with filename for the proper csv file we want.
   *
   * @return string
   *   Indication of success.
   *
   * @throws \exception
   *   Will stop program and log to watchdog.
   */
  protected function parseUpdateUserList($list) {
    $update_list = fopen($this->drupalPath . 'idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_' . $list . '.tsv', 'r');
    // Check if we have anything, if not throw an error.
    if (!isset($update_list)) {
      throw new \exception("Failed to open file at atwork_idir_update/idir/" . $this->timestamp . "/idir_" . $this->timestamp . '_' . $list . '.tsv');
    }
    // Pull the update list.
    while (($row = fgetcsv($update_list, '', "\t")) !== FALSE) {
      // These MUST both have a value, or we cannot make a record.
      if (!isset($row[$this->inputMatrix['name']]) || !isset($row[$this->inputMatrix['field_user_guid']])) {
        // TODO: add a error message on this record.
        continue;
      }
      // Get the GUID of the first user,
      // this will return either an empty set or a user entity number.
      $update_uid = $this->getGUIDField($row[$this->inputMatrix['field_user_guid']]);

      // If we are returned an empty set,
      // we know this user is not in our current db,
      // and in fact needs to be added.
      // We should also do a quick check for username (idir)
      // because we can't duplicate this.
      // If this was the user script, we can simply
      // append them to the add script which will run last.
      if (empty($update_uid)) {
        // Need to check if idir is in user -
        // we cannot have two users with the same idir and different GUID's.
        $new_uid = $this->getUserName($row[$this->inputMatrix['name']]);

        if (!empty($new_uid)) {
          // Setup $this->new_fields. Send to delete function
          // before we add.
          $this->removeUser($row, $new_uid[0]);
        }
        // Setup $this->new_fields for an add and submit -
        // we don't have this guid or idir in the system.
        $result = $this->addUser($row);
      }
      // We have a uid to update that is associated with a guid.
      else {
        // We have a GUID - lets check that the username
        // matches our new username.
        // If not we need to check if this username already exists.
        // Need to check if idir is in user -
        // we cannot have two users with the same idir and different GUID's.
        $match_uid = $this->getUserName($row[$this->inputMatrix['name']]);
        if (isset($match_uid[0]) && $match_uid[0] != $update_uid[0]) {
          // Remove user that already has this idir
          // but a different GUID.
          $this->removeUser($row, $match_uid[0]);
          // Setup $this->new_fields for a delete and submit.
          // Make sure we don't have old values remaining in here.
          unset($this->newFields);
          foreach ($this->inputMatrix as $key => $value) {
            $this->newFields[$value] = $row[$value];
          }
          $result = $this->addUser($row);
          if ($result) {
            AtworkIdirLog::success($result);
          }
          continue;
        }
        else {
          // Set the fields to update the new user with
          // Make sure we are starting fresh first.
          unset($this->newFields);
          // Here we need to get all userfields,
          // and map back the values in the proper row #.
          // TODO: We need to set this up so that the new field numbers
          // point to the column numbers.
          foreach ($this->inputMatrix as $key => $value) {
            $this->newFields[$value] = $row[$value];
          }

          // At this point, we know they are in our system,
          // and should be updated.
          $result = $this->updateSystemUser('update', $update_uid[0], $this->newFields);
        }
      }
      // Log this transaction.
      if ($result) {
        AtworkIdirLog::success($result);
      }
    }
    return "success";
  }

  /**
   * Maps user fields to .csv that should be added to the database.
   *
   * @param array $user_array
   *   Array of values form .csv.
   *
   * @return string
   *   Return result of the update.
   */
  private function addUser(array $user_array) {
    unset($this->newFields);
    // Here we need to get all user fields,
    // and map back the values in the proper row #.
    foreach ($this->inputMatrix as $key => $value) {
      $this->newFields[$value] = $user_array[$value];
    }

    // Calls parent function requires
    // updateSystemUser($type, $uid, array of fields).
    $result = $this->updateSystemUser('add', '', $this->newFields);
    return $result;
  }

  /**
   * Function for removing users.
   *
   * Function that "disables" account.
   * We need to keep them in the system
   * and attached to their content,
   * so we need to keep their display name and guid
   * in case they are reactivated.
   *
   * @param array $user_array
   *   Array of values form the .csv.
   * @param string $uid
   *   The Drupal User id of the profile record.
   *
   * @return string
   *   Return the result of our attempt to update the user.
   */
  private function removeUser(array $user_array, $uid) {
    // We need to make sure we aren't carrying
    // any old information in the new_fields var.
    $this->newFields = NULL;
    // Randomize numbers so we never have to worry about
    // duplicating username.
    $extra_rand = rand(10000, 99999);
    foreach ($this->inputMatrix as $key => $value) {
      if ($key == "name") {
        // Replace username.
        $this->newFields[$value] = 'old_user_' . time() . $extra_rand;
      }
      elseif ($key == "mail") {
        // Replace email.
        $this->newFields[$value] = 'old_user_' . time() . $extra_rand . '@gov.bc.ca';
      }
      elseif ($key == "field_user_guid") {
        // Keep GUID in place, in case we re-activate this user,
        // they will get back their old content.
      }
      else {
        // We are removing all info.
        // We don't want to overwrite name or mail
        // with init or login name,
        // so if it has already been set, ignore.
        if (!isset($this->newFields[$value])) {
          $this->newFields[$value] = '';
        }
      }
    }

    $result = $this->updateSystemUser('delete', $uid, $this->newFields);
    return $result;
  }

  /**
   * Helper function returning uid associated with username.
   *
   * @param string $username
   *   The IDIR of user.
   *
   * @return mixed
   *   Returns an int (uid) or NULL if not found.
   */
  private function getUserName($username) {
    $user_uid = NULL;
    $connection = \Drupal::database();
    $result = $connection->select('users_field_data', 'fd')
      ->fields('fd', array('uid'))
      ->distinct(TRUE)
      ->condition('fd.name', $username, '=')
      ->execute()->fetchCol();
    return $result;
  }

}
