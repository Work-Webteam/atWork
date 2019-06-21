<?php

namespace Drupal\photos\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Field handler to view album photos.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("photos_image")
 */
class PhotosImage extends FieldPluginBase {

  /**
   * Define the available options.
   *
   * @return array
   *   Array of options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_photo'] = ['default' => ''];
    $options['image_style'] = ['default' => ''];

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Link options.
    $form['link_photo'] = [
      '#title' => t("Link image"),
      '#description' => t("Link the image to the album page or image page."),
      '#type' => 'radios',
      '#options' => [
        '' => $this->t('None'),
        'album' => $this->t('Album page'),
        'image' => $this->t('Image page'),
      ],
      '#default_value' => $this->options['link_photo'],
    ];

    // Get image styles.
    $style_options = image_style_options();
    $form['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->options['image_style'],
      '#options' => $style_options,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $render_image = [];
    $image_style = $this->options['image_style'];
    $picture_fid = $this->getValue($values);

    if (!$picture_fid) {
      $node = $values->_entity;
      // Get first image for cover photo.
      if ($node && $node->getType() == 'photos') {
        $nid = $node->id();
        $db = \Drupal::database();
        $picture_fid = $db->query("SELECT fid FROM {photos_image} WHERE pid = :nid ORDER BY fid ASC",
          [':nid' => $nid])->fetchField();
      }
    }

    if ($image_style && $picture_fid) {
      $file = File::load($picture_fid);
      $render_image = [
        '#theme' => 'image_style',
        '#style_name' => $this->options['image_style'],
        '#uri' => $file->getFileUri(),
        '#cache' => [
          'tags' => ['photos:image:' . $picture_fid],
        ],
      ];
    }

    // Add the link if option is selected.
    if ($this->options['link_photo'] == 'image') {
      // Link to image page.
      $image = \Drupal::service('renderer')->render($render_image);
      $link_href = 'base:photos/image/' . $picture_fid;
      $render_image = [
        '#type' => 'link',
        '#title' => $image,
        '#url' => Url::fromUri($link_href),
        '#options' => [
          'attributes' => ['html' => TRUE],
        ],
        '#cache' => [
          'tags' => ['photos:image:' . $picture_fid],
        ],
      ];
    }
    elseif ($this->options['link_photo'] == 'album') {
      // Get album id and link to album page.
      $node = $values->_entity;
      $nid = $node->id();
      $image = \Drupal::service('renderer')->render($render_image);
      $link_href = 'base:photos/album/' . $nid;
      $render_image = [
        '#type' => 'link',
        '#title' => $image,
        '#url' => Url::fromUri($link_href),
        '#options' => [
          'attributes' => ['html' => TRUE],
        ],
        '#cache' => [
          'tags' => ['photos:album:' . $nid, 'photos:image:' . $picture_fid],
        ],
      ];
    }

    return $render_image;
  }

}
