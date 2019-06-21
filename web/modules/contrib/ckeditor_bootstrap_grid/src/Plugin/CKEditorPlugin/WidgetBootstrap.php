<?php

namespace Drupal\ckeditor_bootstrap_grid\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "widgetbootstrap" plugin.
 *
 * @CKEditorPlugin(
 *   id = "widgetbootstrap",
 *   label = @Translation("CKEditor Bootstrap Widgets"),
 *   module = "ckeditor_bootstrap_grid"
 * )
 */
class WidgetBootstrap extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_bootstrap_grid') . '/js/plugins/widgetbootstrap/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
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
  public function getButtons() {
    return [
      'widgetbootstrapLeftCol' => [
        'label' => $this->t('Insert left column box'),
        'image' => drupal_get_path('module', 'ckeditor_bootstrap_grid') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapLeftCol.png',
      ],
      'widgetbootstrapRightCol' => [
        'image' => drupal_get_path('module', 'ckeditor_bootstrap_grid') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapRightCol.png',
        'label' => $this->t('Insert right column box'),
      ],
      'widgetbootstrapTwoCol' => [
        'image' => drupal_get_path('module', 'ckeditor_bootstrap_grid') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapTwoCol.png',
        'label' => $this->t('Insert two column box'),
      ],
      'widgetbootstrapThreeCol' => [
        'image' => drupal_get_path('module', 'ckeditor_bootstrap_grid') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapThreeCol.png',
        'label' => $this->t('Insert three column box'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
