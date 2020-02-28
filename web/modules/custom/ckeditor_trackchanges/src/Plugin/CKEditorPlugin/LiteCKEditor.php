<?php

namespace Drupal\ckeditor_lite\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "lite" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "lite",
 *   label = @Translation("Lite ckeditor")
 * )
 */
class LiteCKEditor extends CKEditorPluginBase {


  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'lite-acceptall' => array(
        'label' => t('Lite ckeditor'),
        'image' => base_path() . 'libraries/lite/icons/lite-acceptall.png',
      ),
      'lite-acceptone' => array(
        'label' => t('Lite ckeditor'),
        'image' => base_path() . 'libraries/lite/icons/lite-acceptone.png',
      ),
      'lite-rejectall' => array(
        'label' => t('Lite ckeditor'),
        'image' => base_path() . 'libraries/lite/icons/lite-rejectall.png',
      ),
      'lite-rejectone' => array(
        'label' => t('Lite ckeditor'),
        'image' => base_path() . 'libraries/lite/icons/lite-rejectone.png',
      ),
      'lite-toggleshow' => array(
        'label' => t('Lite ckeditor'),
        'image' => base_path() . 'libraries/lite/icons/lite-toggleshow.png',
      ),
      'lite-toggletracking' => array(
        'label' => t('Lite ckeditor'),
        'image' => base_path() . 'libraries/lite/icons/lite-toggletracking.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return  base_path() . 'libraries/lite/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
