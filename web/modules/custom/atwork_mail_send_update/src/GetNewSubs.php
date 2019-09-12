<?php

namespace Drupal\atwork_mail_send_update;

use Drupal\Database\Core\Database\Database;


class GetNewSubs {

  /**
   * String that will let us know the id of the newsletter we want subbed.
   *
   * @var string
   */
  protected $newsletterId = '';

  /**
   * Returnable array of users ids.
   *
   * @var array
   *  We will return FALSE if this is empty.
   */
  protected $userIds = [];

  private $uids = [];

  public function __construct($newsletter_id) {
    $this->newsletterId = $newsletter_id;
    $this->uids = findUnsubscribedUsers();
  }

  private function setUserUids($users = []) {
    $this->userIds = $users;
  }

  public function getUserUids(){
    return $this->userIds;
  }

  private function getNewsletterId(){
    return $this->newsletterId;
  }

  private function findUnsubscribedUsers(){
    // Get a connection to the database

    // look up all currently activated users
    // who do not also have a subscription to the newsletter.
    // Use our private setter to pass a public uid array
    // to our getter for the module.
  }
}
