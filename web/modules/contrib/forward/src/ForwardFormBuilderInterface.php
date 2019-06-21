<?php

namespace Drupal\forward;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for building a Forward inline form on an entity.
 */
interface ForwardFormBuilderInterface {

  /**
   * Builds a Forward inline form for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity for which the form is being built.
   * @param array $settings
   *   Array of settings.
   *
   * @return array
   *   A render array for the form.
   */
  public function buildForwardEntityForm(EntityInterface $entity, array $settings);

}
