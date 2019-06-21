<?php

namespace Drupal\photos\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for photos album content.
 *
 * @MigrateSource(
 *   id = "d7_photos",
 *   source_module = "photos"
 * )
 */
class Photos extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('photos_album', 'a')
      ->fields('a', ['pid', 'fid', 'wid', 'count', 'data']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'pid' => $this->t('Photos Album ID'),
      'fid' => $this->t('Album Cover File ID'),
      'wid' => $this->t('Weight'),
      'count' => $this->t('Image count'),
      'data' => $this->t('Album data'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'pid' => [
        'type' => 'integer',
        'alias' => 'a',
      ],
    ];
  }

}
