<?php

/**
 * @file
 * Contains Drupal\user_alert\UserAlertInterface.
 */

namespace Drupal\user_alert;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining User alert entities.
 *
 * @ingroup user_alert
 */
interface UserAlertInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

}
