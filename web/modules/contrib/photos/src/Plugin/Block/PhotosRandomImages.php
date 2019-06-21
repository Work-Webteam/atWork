<?php

namespace Drupal\photos\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\photos\PhotosImage;

/**
 * Provides a 'Photos random images' block.
 *
 * @Block(
 *   id = "photos_random_images",
 *   admin_label = @Translation("Random Images"),
 *   category = @Translation("Photos")
 * )
 */
class PhotosRandomImages extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    // Check if user can view photos.
    if ($account->hasPermission('view photo')) {
      $access = AccessResult::allowed();
    }
    else {
      $access = AccessResult::forbidden();
    }
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve existing configuration for this block.
    // @todo migrate variables to block configuration.
    $config = $this->getConfiguration();
    $count = isset($config['image_count']) ? $config['image_count'] : 10;
    if ($content = PhotosImage::blockView('rand', $count)) {
      return [
        '#markup' => $content,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    // Add a form field to the existing block configuration form.
    $options = array_combine(
      [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30, 40],
      [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30, 40]
    );
    $form['image_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of images to display'),
      '#options' => $options,
      '#default_value' => isset($config['image_count']) ? $config['image_count'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('image_count', $form_state->getValue('image_count'));
  }

}
