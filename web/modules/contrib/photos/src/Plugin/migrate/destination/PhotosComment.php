<?php

namespace Drupal\photos\Plugin\migrate\destination;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Photos comment migration destination.
 *
 * @MigrateDestination(
 *   id = "d7_photos_comment",
 *   destination_module = "photos"
 * )
 */
class PhotosComment extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $db = \Drupal::database();
    $db->insert('photos_comment')
      ->fields([
        'fid' => $row->getDestinationProperty('fid'),
        'cid' => $row->getDestinationProperty('cid'),
      ])
      ->execute();

    return [$row->getDestinationProperty('fid'), $row->getDestinationProperty('cid')];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    $ids['cid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'fid' => 'File ID',
      'cid' => 'Comment ID',
    ];
  }

}
