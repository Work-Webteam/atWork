<?php

namespace Drupal\forward\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Drupal 8 destination for forward statistics.
 *
 * @MigrateDestination(
 *   id = "forward_statistics"
 * )
 */
class ForwardStatistics extends ForwardDestinationBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $destination = $row->getDestination();
    $this->database->merge('forward_statistics')
      ->key(['type' => $destination['type'], 'id' => $destination['id']])
      ->fields([
        'type' => $destination['type'],
        'bundle' => $destination['bundle'],
        'id' => $destination['id'],
        'last_forward_timestamp' => $destination['last_forward_timestamp'],
        'forward_count' => $destination['forward_count'],
        'clickthrough_count' => $destination['clickthrough_count'],
      ])
      ->execute();
    return [
      $row->getDestinationProperty('type'),
      $row->getDestinationProperty('bundle'),
      $row->getDestinationProperty('id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    $ids['id']['type'] = 'integer';

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'type' => $this->t('The entity type, which is always "node".'),
      'bundle' => $this->t('The entity bundle from the node type.'),
      'id' => $this->t('The entity unique ID from the node ID.'),
      'last_forward_timestamp' => $this->t('The date and time the entity was last forwarded.'),
      'forward_count' => $this->t('The number of times the entity was forwarded.'),
      'clickthrough_count' => $this->t('The number of times that the entity was subsequently visited from a link in a forward email.'),
    ];
  }

}
