<?php

namespace Drupal\tether_stats\Entity;

use Drupal\tether_stats\TetherStatsManagerInterface;
use Drupal\tether_stats\TetherStatsStorageInterface;
use Drupal\tether_stats\TetherStatsDerivativeInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a Tether Stats derivative configuration entity.
 *
 * @ConfigEntityType(
 *   id = "tether_stats_derivative",
 *   label = @Translation("Tether Stats derivative"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\tether_stats\Form\TetherStatsDerivativeForm",
 *       "delete" = "Drupal\tether_stats\Form\TetherStatsDerivativeDeleteForm",
 *       "enable" = "Drupal\tether_stats\Form\TetherStatsDerivativeEnableForm",
 *       "disable" = "Drupal\tether_stats\Form\TetherStatsDerivativeDisableForm",
 *     },
 *     "list_builder" = "Drupal\tether_stats\TetherStatsDerivativeListBuilder",
 *   },
 *   admin_permission = "administer tether stats",
 *   config_prefix = "derivative",
 *   entity_keys = {
 *     "id" = "name",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/system/tether-stats/derivatives/{tether_stats_derivative}/add",
 *     "delete-form" = "/admin/config/system/tether-stats/derivatives/{tether_stats_derivative}/delete",
 *     "enable" = "/admin/config/system/tether-stats/derivatives/{tether_stats_derivative}/enable",
 *     "disable" = "/admin/config/system/tether-stats/derivatives/{tether_stats_derivative}/disable",
 *     "collection" = "/admin/config/system/tether-stats/derivatives",
 *   }
 * )
 */
class TetherStatsDerivative extends ConfigEntityBase implements TetherStatsDerivativeInterface {

  /**
   * The name identifier of the derivative.
   *
   * @var string
   */
  protected $name;

  /**
   * The description of the derivative.
   *
   * @var string
   */
  protected $description;

  /**
   * The entity type this derivative is bound to.
   *
   * @var string
   */
  protected $derivativeEntityType;

  /**
   * The bundle this derivative is bound to.
   *
   * @var string
   */
  protected $derivativeBundle;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {

    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getTetherStatsManager() {

    return \Drupal::service('tether_stats.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {

    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeEntityType() {

    return $this->derivativeEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeBundle() {

    return $this->derivativeBundle;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDerivativeEntityType() {

    return !empty($this->derivativeEntityType) && ($this->derivativeEntityType != '*');
  }

  /**
   * {@inheritdoc}
   */
  public function hasDerivativeBundle() {

    return !empty($this->derivativeBundle) && ($this->derivativeBundle != '*');
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeScope() {

    $entity_type = !empty($this->derivativeEntityType) ? $this->derivativeEntityType : '*';
    $bundle = !empty($this->derivativeBundle) ? $this->derivativeBundle : '*';

    return "{$entity_type}:{$bundle}";
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {

    // Only allow delete access if no elements have been created for the
    // derivative. Otherwise, some elements would be based on a derivative
    // that no longer exists.
    if ($operation == 'delete' && $this->getUsageCount() > 0) {

      $access = FALSE;
    }
    else {

      $access = parent::access($operation, $account, $return_as_object);
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsageCount() {

    $count =& drupal_static(__FUNCTION__);

    if (!isset($count)) {

      $storage = $this->getTetherStatsManager()->getStorage();
      $count = $storage->getDerivativeUsageCount($this->id());
    }
    return $count;
  }

  /**
   * Helper callback for uasort() to sort configuration entities.
   *
   *  Sorts by derivativeEntityType, derivativeBundle and name.
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {

    $compare = strnatcasecmp($a->getDerivativeEntityType(), $b->getDerivativeEntityType());

    if ($compare == 0) {

      $compare = strnatcasecmp($a->getDerivativeBundle(), $b->getDerivativeBundle());

      if ($compare == 0) {

        $compare = strnatcasecmp($a->id(), $b->id());
      }
    }
    return $compare;
  }

}
