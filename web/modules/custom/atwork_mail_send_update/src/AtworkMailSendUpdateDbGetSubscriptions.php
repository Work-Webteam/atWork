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
class AtworkMailSendUpdateDbGetSubscriptsions {

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
   *
   */
  protected function setSubscriptionIds() {

  }

  /**
   * Get and return array of newsletter sub ent_ids.
   */
  protected function setNewsletterIds() {

  }

}


