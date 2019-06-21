<?php

namespace Drupal\poll;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for the poll entity.
 *
 * @see \Drupal\poll\Entity\Poll
 */
class PollAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create polls', 'administer polls'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Allow view access if the user has the access polls permission.
    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account, 'access polls');
    }
    elseif ($operation == 'update' && !$account->isAnonymous() && $account->id() == $entity->get('uid')->target_id) {
      return AccessResult::allowedIfHasPermissions($account, [
        'edit own polls',
        'administer polls',
      ], 'OR');
    }
    // Otherwise fall back to the parent which checks the administer polls
    // permission.
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $restricted_fields = [
      'uid',
    ];
    if ($operation === 'edit' && in_array($field_definition->getName(), $restricted_fields, TRUE)) {
      return AccessResult::allowedIfHasPermission($account, 'administer polls');
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }
}
