<?php

namespace Drupal\atwork_idir_update;

use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 * Class AtworkIdirDelete.
 *
 * @package Drupal\atwork_idir_update
 */
class AtworkIdirDelete extends AtworkIdirGUID {

  /**
   * Initiates the parser functions.
   *
   * @return string
   *   Returns result success string.
   *
   * @throws \exception
   *   Stops application if we hit and error and logs.
   */
  public function deleteInit() {
    $delete_status = $this->parseDeleteUserList();
    return $delete_status;
  }

  /**
   * This function does the work of parsing the delete.tsv file.
   *
   * @return string
   *   Return a success message for log.
   *
   * @throws \exception
   *   Cancels application, and logs error.
   */
  private function parseDeleteUserList() {
    $result = NULL;
    // Grab the list of users to be deleted.
    $delete_list = fopen($this->drupalPath . 'idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv', 'r');
    // Check if we have anything, if not throw an error.
    if (!$delete_list) {
      throw new \exception('Failed to open file at' . $this->drupalPath . 'idir/' . $this->timestamp . '/idir_' . $this->timestamp . '_delete.tsv, is this file present?');
    }

    // Pull the delete list.
    while (($row = fgetcsv($delete_list, '', "\t")) !== FALSE) {
      $delete_uid = $this->getGUIDField($row[$this->inputMatrix["field_user_guid"]]);
      // If we are returned an empty set,
      // we know this user is not in our current db,
      // and does not need to be deleted.
      if (empty($delete_uid)) {
        continue;
      }

      // We need a new timestamp appended
      // with a randomized number so we don't hit integrity constraints.
      $extra_rand = rand(10000, 99999);
      foreach ($this->inputMatrix as $key => $value) {
        switch (TRUE) {
          case $key == "name":
            $this->newFields[$value] = 'old_user_' . time() . $extra_rand;
            break;

          case $key == "login":
            $this->newFields[$value] = 'old_user_' . time() . $extra_rand;
            break;

          case $key == "field_user_guid":
            $this->newFields[$value] = $row[$value];
            break;

          case $key == "mail":
          case $key == "init":
            $this->newFields[$value] = 'old_user_' . time() . $extra_rand . '@gov.bc.ca';
            break;

          case $key == "field_user_display_name":
            $this->newFields[$value] = $row[$value];
            // We don't want to remove this -
            // or we get blank users in related content.
            break;

          default:
            $this->newFields[$value] = "";
            break;
        }
      }

      // At this point, we know they are in our system, and should be deleted.
      $result = $this->updateSystemUser('delete', $delete_uid[0], $this->newFields);
      // Log this transaction.
      if ($result) {
        AtworkIdirLog::success($result);
      }
    }
    // We are finished here - let the calling method know we finished.
    return TRUE;
  }

}
