<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Provides a helper method for creating Image fields having assigned the Insert
 * widget.
 */
trait ImageFieldCreationTrait {

  use \Drupal\Tests\image\Kernel\ImageFieldCreationTrait {
    createImageField as imageCreateImageField;
  }

  /**
   * @see \Drupal\Tests\image\Kernel\ImageFieldCreationTrait::createImageField
   *
   * @param string $name
   * @param string $type_name
   * @param array [$storage_settings=array()]
   * @param array [$field_settings=array()]
   * @param array [$widget_settings=array()]
   * @param array [$formatter_settings=array()]
   * @param string [$description='']
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  protected function createImageField($name, $type_name, $storage_settings = array(), $field_settings = array(), $widget_settings = array(), $formatter_settings = array(), $description = '') {
    $field_config = $this->imageCreateImageField($name, $type_name, $storage_settings, $field_settings, $widget_settings, $formatter_settings, $description);

    /** @var EntityDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $type_name . '.default');

    $entity->setComponent($name, array(
        'type' => 'insert_image',
        'settings' => $widget_settings,
      ))
      ->save();

    return $field_config;
  }

}
