<?php

namespace Drupal\forward\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Forward statistics source.
 *
 * @MigrateSource(
 *   id = "forward_log",
 *   source_module = "forward"
 * )
 */
class ForwardLog extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('forward_log', 'f');
    $query = $query->fields('f', [
      'path',
      'type',
      'timestamp',
      'uid',
      'hostname',
    ])
      // The Drupal 8 version of Forward only logs entity forwards at this time.
      // So exclude non-node paths and clickthroughs from the log table migration.
      ->condition('path', 'node/%', 'LIKE')
      ->condition('type', 'SENT')
      ->orderBy('timestamp');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'path' => $this->t('The internal path of the logged item.'),
      'type' => $this->t('The log type, SENT for a forward or REF for a clickthrough.'),
      'timestamp' => $this->t('The date and time the activity was recorded.'),
      'uid' => $this->t('The user ID of the person who performed the action.'),
      'hostname' => $this->t('The IP address of the person who performed the action.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['timestamp']['type'] = 'integer';

    return $ids;
  }

}
