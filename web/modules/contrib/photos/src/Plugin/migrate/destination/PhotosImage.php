<?php

namespace Drupal\photos\Plugin\migrate\destination;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Photos image migration destination.
 *
 * @MigrateDestination(
 *   id = "d7_photos_image",
 *   destination_module = "photos"
 * )
 */
class PhotosImage extends DestinationBase implements ContainerFactoryPluginInterface {

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
    $db->insert('photos_image')
      ->fields([
        'fid' => $row->getDestinationProperty('fid'),
        'pid' => $row->getDestinationProperty('pid'),
        'title' => $row->getDestinationProperty('title'),
        'des' => $row->getDestinationProperty('des'),
        'wid' => $row->getDestinationProperty('wid'),
        'count' => $row->getDestinationProperty('count'),
        'comcount' => $row->getDestinationProperty('comcount'),
        'exif' => $row->getDestinationProperty('exif'),
      ])
      ->execute();

    return [$row->getDestinationProperty('fid')];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'fid' => 'File ID',
      'pid' => 'Photos Album ID',
      'title' => 'Image title',
      'des' => 'Image description',
      'wid' => 'Weight',
      'count' => 'Image views count',
      'comcount' => 'Image comment count',
      'exif' => 'Exif data',
    ];
  }

}
