<?php
Interface iAtworkIdirUpdateUser {
  /**
   * tsvUser:
   *
   * @param [array] $new_user - taken from one line of hte .tsv file
   * This function takes the array, and sends relevant info to the setter
   * @return [object] $user_object - returning a user object after creating it with relevant data from .tsv
   */
  public function tsvUser($new_user);

  /**
   * systemUser
   *
   * @param [string] $guid : the GUID string of the user we want to check on
   * @return [object] $system_user : We will pull this object if the user exists, and send it back
   */
  public function systemUser($guid);
}