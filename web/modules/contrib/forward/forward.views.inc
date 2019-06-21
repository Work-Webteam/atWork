<?php

/**
 * @file
 * Provide views data.
 */

/**
 * Implements hook_views_data().
 */
function forward_views_data() {
  $data = [];

  // Forward statistics.
  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['forward_statistics']['table'] = [
    'group' => t('Forward statistics'),
    'wizard_id' => 'forward_statistics',
  ];

  // For other entity base tables, explain how we join.
  $entity_types = \Drupal::entityTypeManager()->getDefinitions();
  foreach ($entity_types as $type => $info) {
    if (is_a($info, 'Drupal\Core\Entity\ContentEntityType')) {
      if ($info->getBaseTable()) {
        $data['forward_statistics']['table']['join'][$info->getBaseTable() . '_field_data'] = [
          // 'left_field' is the primary key in the referenced table.
          // 'field' is the foreign key in this table.
          'left_field' => $info->getKey('id'),
          'field' => 'id',
          'extra' => [
            ['field' => 'type', 'value' => $type],
          ],
        ];
      }
    }
  }

  // Forward statistics fields.
  $data['forward_statistics']['forward_count'] = [
    'title' => t('Forward count'),
    'help' => t('The number of times an entity was forwarded.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['forward_statistics']['last_forward_timestamp'] = [
    'title' => t('Most recent forward'),
    'help' => t('The last time an entity was forwarded.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Forward log.
  $data['forward_log']['table'] = [
    'group' => t('Forward log'),
    'wizard_id' => 'forward_log',
  ];

  // Forward log fields.
  $data['forward_log']['path'] = [
    'title' => t('Path'),
    'help' => t('The path that was forwarded.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['forward_log']['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('The date and time of the forward.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['forward_log']['uid'] = [
    'title' => t('UID'),
    'help' => t('The user ID of the user who forwarded.'),
    'field' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'search' => [
      'id' => 'standard',
    ],
    'relationship' => [
      'title' => t('User'),
      'label' => t('User referenced by Forward log'),
      'help' => t('The user that forwarded.'),
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
    ],
  ];
  $data['forward_log']['hostname'] = [
    'title' => t('Hostname'),
    'help' => t('Hostname of the user who forwarded.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function forward_views_data_alter(&$data) {
  $entity_types = \Drupal::entityTypeManager()->getDefinitions();
  foreach ($entity_types as $type => $info) {
    if (is_a($info, 'Drupal\Core\Entity\ContentEntityType')) {
      if ($info->getBaseTable()) {
        $label = (string) $info->getLabel();
        // Add a Forward link field.
        $data[$info->getBaseTable()]['forward_link'] = [
          'field' => [
            'title' => t('Forward link'),
            'group' => t($label),
            'help' => t('Provide a simple link to forward the @entity-type to a friend.', ['@entity-type' => strtolower(t($label))]),
            'id' => 'forward_link',
          ],
        ];
        // Add a relationship to the logs.
        $data[$info->getBaseTable() . '_field_data']['forward_log'] = [
          'title' => t('Entity with forward logs'),
          'help' => t('Relate all forward logs for the entity. Creates a row for each instance the entity was forwarded.'),
          'relationship' => [
            'group' => t('Forward log'),
            'label' => t('Forward log referenced by entity'),
            'base' => 'forward_log',
            'base field' => 'id',
            'relationship field' => $info->getKey('id'),
            'id' => 'standard',
            'extra' => [
              [
                'field' => 'type',
                'value' => $type,
              ],
            ],
          ],
        ];
      }
    }
  }
}
