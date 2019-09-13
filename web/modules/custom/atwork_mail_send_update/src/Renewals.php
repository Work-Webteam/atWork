<?php

namespace Drupal\atwork_mail_send_update;

use Drupal\Database\Core\Database\Database;

/**
 * Class AtworkMailSendUpdateDbGetSubscriptions.
 *
 * We need to override two functions so that we can get renewals
 * instead of unsubs.
 */
class Renewals extends Subscriptions {

  /**
   * Override setter for subscription renewals.
   *
   * @inheritDoc
   */
  protected function setSubscriptionIds() {
    // Create a Database connection to get status of subscriptions
    // belonging to active users.
    $connection = \Drupal::database();
    $query = $connection->query(
      "SELECT ufd.uid 
        FROM {users_field_data} ufd 
        INNER JOIN {user__message_subscribe_email} sub 
        ON ufd.uid = sub.entity_id 
        WHERE ufd.status = 1 && 
        sub.message_subscribe_email_value = 0"
    );
    $this->userIds = $query->fetchAll();
  }

  /**
   * Override setter for newsletter renewals.
   *
   * @inheritDoc
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
        WHERE ss.status = 1 AND sss.subscriptions_status = 0"
    );
    $this->userIds = $query->fetchAll();
  }

}
