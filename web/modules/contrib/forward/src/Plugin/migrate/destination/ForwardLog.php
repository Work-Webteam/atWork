<?php

namespace Drupal\forward\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Drupal 8 destination for forward logs.
 *
 * @MigrateDestination(
 *   id = "forward_log"
 * )
 */
class ForwardLog extends ForwardDestinationBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $destination = $row->getDestination();
    $this->database->insert('forward_log')
      ->fields([
        'type' => $destination['type'],
        'id' => intval($destination['id']),
        'path' => $destination['path'],
        'action' => $destination['action'],
        'timestamp' => $destination['timestamp'],
        'uid' => $destination['uid'],
        'hostname' => $destination['hostname'],
      ])
      ->execute();
    return [
      $row->getDestinationProperty('type'),
      $row->getDestinationProperty('id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    $ids['id']['type'] = 'integer';

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'type' => $this->t('The entity type, which is always "node".'),
      'id' => $this->t('The entity unique ID from the node ID.'),
      'path' => $this->t('The internal path of the logged item.'),
      'action' => $this->t('The log action, SENT for a forward or REF for a clickthrough.'),
      'timestamp' => $this->t('The date and time the activity was recorded.'),
      'uid' => $this->t('The user ID of the person who performed the action.'),
      'hostname' => $this->t('The IP address of the person who performed the action.'),
    ];
  }

}
