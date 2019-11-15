<?php

/**
 * @file
 * Contains Drupal\user_alert\Entity\Form\UserAlertForm.
 */

namespace Drupal\user_alert\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for User alert edit forms.
 *
 * @ingroup user_alert
 */
class UserAlertForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\user_alert\Entity\UserAlert */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();
    $type = $entity->bundle();
    $label = $entity->label();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the @user_alert_type alert %label.', [
          '@user_alert_type' => $type,
          '%label' => $label,
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the @%user_alert_type alert %label.', [
          '@user_alert_type' => $type,
          '%label' => $label,
        ]));
    }

    $form_state->setRedirect('entity.user_alert.collection', ['user_alert' => $entity->id()]);
  }

}
