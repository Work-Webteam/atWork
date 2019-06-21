<?php

namespace Drupal\term_merge_manager\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Term merge from edit forms.
 *
 * @ingroup term_merge_manager
 */
class TermMergeFromForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\term_merge_manager\Entity\TermMergeFrom */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Term merge from.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Term merge from.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.term_merge_from.canonical', ['term_merge_from' => $entity->id()]);
  }

}
