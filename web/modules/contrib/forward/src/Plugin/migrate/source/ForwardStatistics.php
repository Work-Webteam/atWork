<?php

namespace Drupal\forward\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Forward statistics source.
 *
 * @MigrateSource(
 *   id = "forward_statistics",
 *   source_module = "forward"
 * )
 */
class ForwardStatistics extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('forward_statistics', 'f');
    $query->join('node', 'n', 'f.nid = n.nid');
    $query = $query->fields('f', [
      'nid',
      'last_forward_timestamp',
      'forward_count',
      'clickthrough_count',
    ])
      ->fields('n', ['type'])
      ->orderBy('nid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Primary key: the node ID.'),
      'type' => $this->t('The node type or bundle.'),
      'last_forward_timestamp' => $this->t('The date and time the node was last forwarded.'),
      'forward_count' => $this->t('The number of times the node was forwarded.'),
      'clickthrough_count' => $this->t('The number of times that the node was subsequently visited from a link in a forward email.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';

    return $ids;
  }

}
