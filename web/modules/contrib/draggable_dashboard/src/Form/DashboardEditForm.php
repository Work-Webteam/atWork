<?php

namespace Drupal\draggable_dashboard\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\draggable_dashboard\Entity\DashboardEntity;

/**
 * Provides the draggable dashboard edit form.
 */
class DashboardEditForm extends DashboardFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'draggable_dashboard_edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDashboard($did) {
    return DashboardEntity::load($did);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $did = NULL) {
    $form = parent::buildForm($form, $form_state, $did);

    $form['#title'] = $this->dashboard->get('title');
    $form['did'] = [
      '#type' => 'hidden',
      '#value' => $this->dashboard->id(),
    ];

    $url = new Url('draggable_dashboard.delete_dashboard', [
      'did' => $this->dashboard->id(),
    ]);

    $form['actions']['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      '#url' => $url,
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
    ];

    return $form;
  }
}
