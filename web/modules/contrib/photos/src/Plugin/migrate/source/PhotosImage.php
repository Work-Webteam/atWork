<?php

namespace Drupal\photos\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for photos image content.
 *
 * @MigrateSource(
 *   id = "d7_photos_image",
 *   source_module = "photos"
 * )
 */
class PhotosImage extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('photos_image', 'i')
      ->fields('i', [
        'fid',
        'pid',
        'title',
        'des',
        'wid',
        'count',
        'comcount',
        'exif',
      ]);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'fid' => $this->t('File ID'),
      'pid' => $this->t('Photos Album ID'),
      'title' => $this->t('Image title'),
      'des' => $this->t('Image description'),
      'wid' => $this->t('Weight'),
      'count' => $this->t('Image views count'),
      'comcount' => $this->t('Image comment count'),
      'exif' => $this->t('Exif data'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'fid' => [
        'type' => 'integer',
        'alias' => 'i',
      ],
    ];
  }

}
