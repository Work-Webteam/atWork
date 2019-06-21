<?php

/**
 * @file
 * Contains likeit.views.inc.
 */

/**
 * Implements hook_views_data().
 */
function likeit_views_data() {
  $data['likeit']['table']['group'] = t('Like It!');
  $data['likeit']['table']['join'] = [
    '#global' => [],
  ];

  $data['likeit']['likeit_target_entity_title_views_field'] = [
    'title' => t('Target Entity Title'),
    'help' => t('Providing target entity title that user has liked.'),
    'field' => [
      'id' => 'likeit_target_entity_title_views_field',
    ],
  ];

  $data['likeit']['likeit_target_entity_view_views_field'] = [
    'title' => t('Target Entity View'),
    'help' => t('Providing target entity view.'),
    'field' => [
      'id' => 'likeit_target_entity_view_views_field',
    ],
  ];

  return $data;
}
