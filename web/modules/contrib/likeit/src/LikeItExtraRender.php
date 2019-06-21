<?php

namespace Drupal\likeit;

use Drupal\extrafield_views_integration\lib\ExtrafieldRenderClassInterface;
use Drupal\Core\Entity\Entity;

/**
 * Class LikeItExtraRender to add Likeit extra field to the views.
 */
class LikeItExtraRender implements ExtrafieldRenderClassInterface {

  /**
   * {@inheritdoc}
   */
  public static function render(Entity $entity) {
    $config = \Drupal::config('likeit.settings');
    $target_entities = $config->get('target_entities');
    $likeit = '';

    if (!empty($target_entities)) {
      $target = $entity->getEntityTypeId() . ':' . $entity->bundle();
      if (in_array($target, $target_entities)) {
        $likeit = likeit_get_link($target, $entity->id());
      }
    }

    return $likeit;
  }

}
