<?php

namespace Drupal\atwork_mail_send_update;

use Drupal\Database\Core\Database\Database;

/**
 * Class GetNewSubs.
 *
 * @package Drupal\atwork_mail_send_update
 */
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

  /**
   * GetNewSubs constructor.
   *
   * @param string $newsletter_id
   *   The machine name of the main newsletter.
   */
  public function __construct($newsletter_id) {
    $this->setNewsletterId($newsletter_id);
    $this->findUnsubscribedUsers();
  }

  /**
   * Setter for user ids, accessible by getter.
   *
   * @param array $users
   *   An array of $uids.
   */
  private function setUserUids(array $users) {
    $this->userIds = $users;
  }

  /**
   * Setter for newsletter id.
   *
   * @param string $id
   *   The machine name of the newsletter.
   */
  protected function setNewsletterId($id) {
    $this->newsletterId = $id;
  }

  /**
   * Getter for uid array.
   *
   * @return array
   *   Returns an array of uids.
   */
  public function getUserUids() {
    return $this->userIds;
  }

  /**
   * Internal getter for newsletter id.
   *
   * @return string
   *   Returns the machine name of relevant newsletter passed
   *   to this class.
   */
  private function getNewsletterId() {
    return $this->newsletterId;
  }

  /**
   * Database call that looks for users who have no subscription to Newsletter.
   */
  private function findUnsubscribedUsers() {
    // Get a connection to the database.
    // look up all currently activated users
    // who do not also have a subscription to the newsletter.
    // Haven't figured out how to do this without a sub-query yet -
    // but should be fairly light (i.e. not called multiple times).
    $newsletter = $this->getNewsletterId();
    $connection = \Drupal::database();
    $query = $connection->query(
      "SELECT u.uid
        FROM {users_field_data} u
         WHERE u.uid NOT IN (
            SELECT s.uid
            FROM {simplenews_subscriber} s
            LEFT JOIN {simplenews_subscriber__subscriptions} ss 
            ON s.id = ss.entity_id
            WHERE ss.subscriptions_target_id = $newsletter;
         ) 
         AND u.status = 1"
    );
    $user_ids = $query->fetchAll();
    // Give these to our private setter, make them available to external getter.
    $this->setUserUids($user_ids);
  }

}
