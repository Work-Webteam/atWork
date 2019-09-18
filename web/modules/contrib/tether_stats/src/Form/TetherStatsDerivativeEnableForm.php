<?php

namespace Drupal\tether_stats\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form to delete a derivative.
 */
class TetherStatsDerivativeEnableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to enable this derivative?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Enabling this derivative will activate stat collection for any valid identity sets referencing it.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

    return new Url('entity.tether_stats_derivative.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->entity->set('status', TRUE);
    $this->entity->save();

    $this->messenger()->addMessage($this->t('The derivative %derivative has been enabled. You may now use this derivative to define additional tracking elements.',
      ['%derivative' => $this->entity->id()]));

    $form_state->setRedirect('entity.tether_stats_derivative.collection');
  }

}
