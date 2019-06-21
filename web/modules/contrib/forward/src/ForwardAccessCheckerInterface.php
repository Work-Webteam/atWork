<?php

namespace Drupal\forward;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for checking access to Forward link or form display.
 */
interface ForwardAccessCheckerInterface {

  /**
   * Checks whether a Forward link or form can be displayed on a given entity and view mode.
   *
   * @param array $settings
   *   Array of settings.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity for which the link is being built.
   * @param string $view_mode
   *   The view mode to check.
   * @param string $entity_type
   *   The entity_type to check if an entity is not available at the time the
   *   check needs to occur.
   * @param string $bundle
   *   The bundle to check if an entity is not available at the time the check
   *   needs to occur.
   *
   * @return bool
   *   Whether access is allowed or not.
   */
  public function isAllowed(array $settings, EntityInterface $entity, $view_mode, $entity_type = NULL, $bundle = NULL);

}
