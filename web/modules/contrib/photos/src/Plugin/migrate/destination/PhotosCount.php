<?php

namespace Drupal\photos\Plugin\migrate\destination;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Photos count migration destination.
 *
 * @MigrateDestination(
 *   id = "d7_photos_count",
 *   destination_module = "photos"
 * )
 */
class PhotosCount extends DestinationBase implements ContainerFactoryPluginInterface {

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

    Database::getConnection('default')->merge('photos_count')
      ->key(['id' => $row->getDestinationProperty('id')])
      ->fields([
        'cid' => $row->getDestinationProperty('cid'),
        'changed' => $row->getDestinationProperty('changed'),
        'type' => $row->getDestinationProperty('type'),
        'value' => $row->getDestinationProperty('value'),
      ])
      ->execute();

    return [$row->getDestinationProperty('id')];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'id' => 'ID',
      'cid' => 'CID',
      'changed' => 'Last updated',
      'type' => 'Type',
      'value' => 'Count value',
    ];
  }

}
