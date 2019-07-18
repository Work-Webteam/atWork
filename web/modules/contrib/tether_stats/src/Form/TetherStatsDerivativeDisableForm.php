<?php

namespace Drupal\tether_stats\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form to delete a derivative.
 */
class TetherStatsDerivativeDisableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to disable this derivative?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Disabling this derivative will cause any identity sets referencing it to be ignored for stat collection.');
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

    $this->entity->set('status', FALSE);
    $this->entity->save();

    $this->messenger()->addMessage($this->t('The derivative %derivative has been disabled. Future identity sets referencing this derviative will not be tracked.',
      ['%derivative' => $this->entity->id()]));

    $form_state->setRedirect('entity.tether_stats_derivative.collection');
  }

}
