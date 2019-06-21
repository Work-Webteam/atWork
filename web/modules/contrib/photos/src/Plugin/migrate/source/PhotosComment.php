<?php

namespace Drupal\photos\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for photos image comment content.
 *
 * @MigrateSource(
 *   id = "d7_photos_comment",
 *   source_module = "photos"
 * )
 */
class PhotosComment extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('photos_comment', 'c')
      ->fields('c', ['fid', 'cid']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'fid' => $this->t('File ID'),
      'cid' => $this->t('Comment ID'),
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
        'alias' => 'c',
      ],
      'cid' => [
        'type' => 'integer',
        'alias' => 'c',
      ],
    ];
  }

}
