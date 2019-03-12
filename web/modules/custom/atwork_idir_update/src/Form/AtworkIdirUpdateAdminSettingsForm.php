<?php

namespace Drupal\atwork_idir_update\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AtworkIdirUpdateAdminSettingsForm.
 */
class AtworkIdirUpdateAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'atwork_idir_update.atworkidirupdateadminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atwork_idir_update_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('atwork_idir_update.atworkidirupdateadminsettings');
    $form['idir_ftp_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FTP Location'),
      '#description' => $this->t('Enter the location that you will be retrieving the update from'),
      '#maxlength' => 264,
      '#size' => 128,
      '#default_value' => $config->get('idir_ftp_location'),
    ];
    $form['idir_login_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login Name'),
      '#description' => $this->t('Enter your login name'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('idir_login_name'),
    ];
    $form['idir_login_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login Password'),
      '#description' => $this->t('Enter the password you use to download the reports'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('idir_login_password'),
    ];
    // We need a way to count additional fields if any were saved, then add them to the form via Ajax.
    $form['idir_generate_fields'] = [
      '#type' => 'submit',
      '#title' => $this->t('Generate Fields'),
      '#value' => $this->t('Generate Fields'),
      '#description' => $this->t('Generate the fields available in the data file'),
      '#default_value' => $config->get('idir_generate_fields'),
      '#ajax' => [
        'callback' => [$this, 'idirGenerateFields'],
        'wrapper' => 'names-fieldset-wrapper',
      ],
    ];
    $form['names_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('People coming to picnic'),
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // TODO: Gather all additional fields (if any) and save from form-state dropdown (from fieldset).
    $this->config('atwork_idir_update.atworkidirupdateadminsettings')
      ->set('idir_ftp_location', $form_state->getValue('idir_ftp_location'))
      ->set('idir_login_name', $form_state->getValue('idir_login_name'))
      ->set('idir_login_password', $form_state->getValue('idir_login_password'))
      ->set('idir_generate_fields', $form_state->getValue('idir_generate_fields'))
      ->save();
  }


  // LABEL == column name, dropdown = available user fields.
  public function idirGenerateFields(array &$form, FormStateInterface $form_state){
    // Add a fields to our config if required
    // TODO: Function to grab just the .csv labels and return them
    // TODO: Foreach the labels, then add them to the fieldset
    $config = $this->config('atwork_idir_update.atworkidirupdateadminsettings');
    ksm('We did it!');
    $form['names_fieldset']['idir_test_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test field'),
      '#description' => $this->t('Test field'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('idir_test_fields'),
    ];

    return $form['names_fieldset'];
  }
}
