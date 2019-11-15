<?php

/**
 * @file
 * Contains Drupal\user_alert\UserAlertTypeInterface.
 */

namespace Drupal\user_alert;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining User alert type entities.
 */
interface UserAlertTypeInterface extends ConfigEntityInterface {
  /**
   * Gets the description.
   *
   * @return string
   *   The description of this entity type.
   */
  public function getDescription();
}
