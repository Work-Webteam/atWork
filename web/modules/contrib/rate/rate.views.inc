<?php

/**
 * @file
 * rate.views.inc
 */

/**
 * Implements hook_views_data_alter().
 */
function rate_views_data_alter(array &$data) {
  $entity_types = \Drupal::entityTypeManager()->getDefinitions();

  foreach ($entity_types as $entity_type_id => $entity_type) {
    $data[$entity_type_id][$entity_type_id . '_rate_field'] = [
      'title' => t('Rate'),
      'field' => [
        'title' => t('Rate'),
        'help' => t('Displays a widget to vote this entity.'),
        'id' => 'rate_field',
      ],
      'filter' => [
        'title' => t('Rate'),
        'help' => t('Allow a filtering data per vote average.'),
        'id' => 'rate_filter',
      ],
      'sort' => [
        'title' => t('Rate'),
        'help' => t('Allow soring by vote average.'),
        'id' => 'rate_sort',
      ],
    ];
  }
}
