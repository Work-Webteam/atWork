<?php

namespace Drupal\tether_stats\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Form to delete a derivative.
 */
class TetherStatsDerivativeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete this derivative?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {

    return t('The derivative %name will be permanently removed from the system.', ['%name' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {

    return $this->t('Deleted Tether Stats derivative %name.', ['%name' => $this->entity->id()]);
  }

}
