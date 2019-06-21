<?php
/**
 * Created by PhpStorm.
 * User: dasricardo
 * Date: 01.04.16
 * Time: 16:30
 */

namespace Drupal\extrafield_views_integration\lib;

use Drupal\Core\Entity\Entity;

/**
 * Interface ExtrafieldCallbackInterface
 * @package Drupal\extrafield_views_integration
 */
interface ExtrafieldRenderClassInterface {
    /**
     * Render function will be called from views.
     * @param Entity $entity
     * @return mixed
     */
    public static function render(Entity $entity);
}