<?php

namespace Drupal\atwork_mail_send_update;

use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Class AtworkMailSendUpdateDbGetSubscriptions.
 *
 * This class is the parent of the parse classes.
 * We deal with updating users and checking users here,
 * but this class won't be invoked on its own.
 */
class AtworkMailSendUpdateDbGetSubscriptions {

  /**
   * @var array
   *  returnable array of users ids.
   *  We will return FALSE if this is empty.
   */
  protected $userIds = [];

  /**
   * AtworkMailSendUpdateDbGetSubscriptions constructor.
   */
  public function __construct($type) {
    $userIds = setUserIds($type);
  }

  /**
   * Primary setter that passes off actual work to other protected functions.
   *
   * @param string $type
   *   Denotes the type of setter we want.
   */
  protected function setUserIds($type) {
    if ($type == "subscriptions") {
      $this->userIds = setSubscriptionIds();
    }
    if ($type == "newsletter") {
      $this->userIds = setNewsletterIds();
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
   * Get and return an array of subscriptions that are assigned to a
   * user that is no longer active.
   */
  protected function setSubscriptionIds() {
    $this->userIds = oldSubscriptions();
  }

  /**
   * Get and return array of newsletter sub ent_ids that are assigned to
   * a user that is no longer active
   */
  protected function setNewsletterIds() {
    $this->userIds = oldNewsSubscriptions();
  }

  /**
   * @return mixed
   */
  protected function oldSubscriptions() {
    // Create a Database connection to get all subscriptions
    // belonging to blocked users.
    $connection = \Drupal::database();
    $query = $connection->query("select ufd.uid from {users_field_data} ufd inner join {user__message_subscribe_email} sub on ufd.uid = sub.entity_id where ufd.status = 0 && sub.message_subscribe_email_value = 1");
    $subs = $query->fetchAll();
    return $subs;
  }
  protected function oldNewsSubscriptions() {
    // Create Database connection to get all newsletter subscriptions
    // bleongin to blocked users.
    $connection = \Drupal::database();
    $query = $connection->query("select ss.uid from {users_field_data} ufd inner join {simplenews_subscriber} ss on ufd.uid = ss.uid where ufd.status = 0");
    $subs = $query->fetchAll();
    return $subs;
  }

}


