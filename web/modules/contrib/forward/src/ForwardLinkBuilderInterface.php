<?php

namespace Drupal\forward;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for building a Forward link on an entity.
 */
interface ForwardLinkBuilderInterface {

  /**
   * Builds a Forward link for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity for which the link is being built.
   * @param array $settings
   *   Array of settings.
   *
   * @return array
   *   A render array for the link.
   */
  public function buildForwardEntityLink(EntityInterface $entity, array $settings);

}
