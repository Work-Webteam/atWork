<?php

namespace Drupal\photos\Plugin\migrate\destination;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Photos migration destination.
 *
 * @MigrateDestination(
 *   id = "d7_photos",
 *   destination_module = "photos"
 * )
 */
class Photos extends DestinationBase implements ContainerFactoryPluginInterface {

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
    // @todo build storage interface for photos?
    // @todo look up node->nid in migration system and match pid!
    // @todo add support to check if pid exists.
    $db = \Drupal::database();
    $path = $db->update('photos_album')
      ->fields([
        'fid' => $row->getDestinationProperty('fid'),
        'wid' => $row->getDestinationProperty('wid'),
        'count' => $row->getDestinationProperty('count'),
        'data' => $row->getDestinationProperty('data'),
      ])
      ->condition('pid', $row->getDestinationProperty('pid'))
      ->execute();

    return [$path['pid']];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['pid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'pid' => 'Photos Album ID',
      'fid' => 'Album Cover File ID',
      'wid' => 'Weight',
      'count' => 'Image count',
      'data' => 'Album data',
    ];
  }

}
