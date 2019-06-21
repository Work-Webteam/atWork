<?php

namespace Drupal\photos_access\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for photos album access.
 *
 * @MigrateSource(
 *   id = "d7_photos_access",
 *   source_module = "photos_access"
 * )
 */
class PhotosAccess extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('photos_access_album', 'a')
      ->fields('a', ['id', 'nid', 'viewid', 'pass']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('ID'),
      'nid' => $this->t('Node ID'),
      'viewid' => $this->t('Access type'),
      'pass' => $this->t('Password'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'a',
      ],
    ];
  }

}
