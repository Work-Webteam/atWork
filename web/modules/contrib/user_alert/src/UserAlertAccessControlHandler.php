<?php

/**
 * @file
 * Contains Drupal\user_alert\UserAlertAccessControlHandler.
 */

namespace Drupal\user_alert;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the User alert entity.
 *
 * @see \Drupal\user_alert\Entity\UserAlert.
 */
class UserAlertAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view user alert entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit user alert entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete user alert entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add user alert entities');
  }

}
