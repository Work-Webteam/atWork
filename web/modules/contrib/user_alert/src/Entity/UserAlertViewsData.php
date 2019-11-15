<?php

/**
 * @file
 * Contains Drupal\user_alert\Entity\UserAlert.
 */

namespace Drupal\user_alert\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for User alert entities.
 */
class UserAlertViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['user_alert']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('User Alert'),
      'help' => $this->t('The User Alert ID.'),
    );

    return $data;
  }

}
