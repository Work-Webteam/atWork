<?php

namespace Drupal\atwork_mail_send_update;

use Drupal\Database\Core\Database\Database;

/**
 * Class AtworkMailSendUpdateDbGetSubscriptions.
 *
 * This class is the parent of the parse classes.
 * We deal with updating users and checking users here,
 * but this class won't be invoked on its own.
 */
class AtworkMailSendUpdateDbGetSubscriptions {

  /**
   * Returnable array of users ids.
   *
   * @var array
   *  We will return FALSE if this is empty.
   */
  protected $userIds = [];

  /**
   * AtworkMailSendUpdateDbGetSubscriptions constructor.
   *
   * @param string $type
   *   String that lets us know if we want a newsletter or subscription
   *   array.
   */
  public function __construct($type) {
    $this->setUserIds($type);
  }

  /**
   * Primary setter that passes off actual work to other protected functions.
   *
   * @param string $type
   *   Denotes the type of setter we want.
   */
  protected function setUserIds($type) {
    if ($type == "subscriptions") {
      $this->setSubscriptionIds();
    }
    if ($type == "newsletter") {
      $this->setNewsletterIds();
    }
  }

  /**
   * Getter for user id array.
   *
   * @return array
   *   An array of user ids to update.
   */
  public function getUserIds() {
    return $this->userIds;
  }

  /**
   * Array of subscriptions that are no longer active.
   */
  protected function setSubscriptionIds() {
    // Create a Database connection to get all subscriptions
    // belonging to blocked users.
    $connection = \Drupal::database();
    $query = $connection->query(
        "SELECT ufd.uid 
        FROM {users_field_data} ufd 
        INNER JOIN {user__message_subscribe_email} sub 
        ON ufd.uid = sub.entity_id 
        WHERE ufd.status = 0 && 
        sub.message_subscribe_email_value = 1"
    );
    $this->userIds = $query->fetchAll();
  }

  /**
   * Array of subscription entity_ids that are no longer active.
   */
  protected function setNewsletterIds() {
    // Create Database connection to get all newsletter subscriptions
    // belonging to blocked users.
    $connection = \Drupal::database();
    $query = $connection->query(
      "SELECT ss.id
        FROM {simplenews_subscriber} ss
        LEFT JOIN {simplenews_subscriber__subscriptions} sss
        ON sss.entity_id = ss.id
        WHERE ss.status = 0 AND sss.subscriptions_status = 1"
    );
    $this->userIds = $query->fetchAll();
  }

}
