<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a helper method for creating text fields.
 */
trait TextFieldCreationTrait {

  /**
   * Creates a new file field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param string [$type='text_long']
   *   Type of the text field.
   * @param array [$storage_settings=array()]
   *   A list of field storage settings that will be added to the
   *   defaults.
   * @param array [$field_settings=array()]
   *   A list of instance settings that will be added to the instance
   *   defaults.
   * @param array [$widget_settings=array()]
   *   Widget settings to be added to the widget defaults.
   * @param array [$formatter_settings=array()]
   *   Formatter settings to be added to the formatter defaults.
   * @param string [$description='']
   *   A description for the field. Defaults to ''.
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createTextField($name, $type_name, $type = 'text_long', $storage_settings = array(), $field_settings = array(), $widget_settings = array(), $formatter_settings = array(), $description = '') {
    FieldStorageConfig::create(array(
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => $type,
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ))->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => 'node',
      'bundle' => $type_name,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
      'description' => $description,
    ]);
    $field_config->save();

    /** @var EntityDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $type_name . '.default');

    $entity
      ->setComponent($name, array(
        'type' => 'string_textarea',
        'settings' => $widget_settings,
      ))
      ->save();

    /** @var EntityDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $type_name . '.default');

    $entity
      ->setComponent($name, array(
        'type' => 'basic_string',
        'settings' => $formatter_settings,
      ))
      ->save();

    return $field_config;
  }

}
