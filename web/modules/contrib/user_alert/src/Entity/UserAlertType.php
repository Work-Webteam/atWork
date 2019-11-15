<?php

/**
 * @file
 * Contains Drupal\user_alert\Entity\UserAlertType.
 */

namespace Drupal\user_alert\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\user_alert\UserAlertTypeInterface;

/**
 * Defines the User alert type entity.
 *
 * @ConfigEntityType(
 *   id = "user_alert_type",
 *   label = @Translation("User alert type"),
 *   handlers = {
 *     "list_builder" = "Drupal\user_alert\UserAlertTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\user_alert\Form\UserAlertTypeForm",
 *       "edit" = "Drupal\user_alert\Form\UserAlertTypeForm",
 *       "delete" = "Drupal\user_alert\Form\UserAlertTypeDeleteForm"
 *     }
 *   },
 *   config_prefix = "user_alert_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "user_alert",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/user_alert_type/{user_alert_type}",
 *     "edit-form" = "/admin/structure/user_alert_type/{user_alert_type}/edit",
 *     "delete-form" = "/admin/structure/user_alert_type/{user_alert_type}/delete",
 *     "collection" = "/admin/structure/visibility_group"
 *   }
 * )
 */
class UserAlertType extends ConfigEntityBundleBase implements UserAlertTypeInterface {
  /**
   * The User alert type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The User alert type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this user alert type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }
}
