<?php

namespace Drupal\forward;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Defines a class for checking whether a Forward link or form can be displayed.
 */
class ForwardAccessChecker implements ForwardAccessCheckerInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a ForwardAccessChecker object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed(array $settings, EntityInterface $entity = NULL, $view_mode = NULL, $entity_type = NULL, $bundle = NULL) {
    // Full and default are synonymous.
    if ($view_mode == 'default') {
      $view_mode = 'full';
    }
    // Check permission.
    $show = $this->currentUser->hasPermission('access forward');
    // Check view mode.
    if ($show && $view_mode) {
      $show = !empty($settings['forward_view_' . $view_mode]);
    }
    // Check entity type.
    if ($show) {
      if ($entity) {
        $entity_type = $entity->getEntityTypeId();
      }
      $show = $entity_type ? !empty($settings['forward_entity_' . $entity_type]) : FALSE;
    }
    // Check entity bundle.
    if ($show) {
      if ($entity) {
        $bundle = $entity->bundle();
      }
      $show = ($entity_type && $bundle) ? !empty($settings['forward_' . $entity_type . '_' . $bundle]) : FALSE;
    }
    return $show;
  }

}
