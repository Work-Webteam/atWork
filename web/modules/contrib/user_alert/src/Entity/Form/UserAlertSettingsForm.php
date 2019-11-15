<?php

/**
 * @file
 * Contains Drupal\user_alert\Entity\Form\UserAlertSettingsForm.
 */

namespace Drupal\user_alert\Entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserAlertSettingsForm.
 *
 * @package Drupal\user_alert\Form
 *
 * @ingroup user_alert
 */
class UserAlertSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'UserAlert_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }


  /**
   * Defines the settings form for User alert entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['UserAlert_settings']['#markup'] = 'Settings form for User alert entities. Manage field settings here.';
    return $form;
  }

}
