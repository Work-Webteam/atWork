<?php

namespace Drupal\draggable_dashboard\Form;

use Drupal\draggable_dashboard\Entity\DashboardEntity;

/**
 * Provides the path add form.
 */
class DashboardAddForm extends DashboardFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'draggable_dashboard_add';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDashboard($did) {
    return DashboardEntity::create([
      'title' => '',
      'description' => '',
      'columns' => 2,
      'blocks' => ''
    ]);
  }

}
