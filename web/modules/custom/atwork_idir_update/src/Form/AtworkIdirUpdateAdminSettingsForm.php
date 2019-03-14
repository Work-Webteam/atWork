<?php

namespace Drupal\atwork_idir_update\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\atwork_idir_update\Controller\AtworkIdirUpdateController;
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
   *  // TODO: need to get user fields, and drop default fields we don't want to mess with.
   */

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('atwork_idir_update.atworkidirupdateadminsettings');
    // We want to rebuild the form build every time we display it and show contextual inline messages validation
    $form['#cache']['max-age'] = 0;

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
      '#title' => $this->t('Map import to user fields'),
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['#validate'][] = [$this, 'idirValidateFields'];
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
    $config = $this->config('atwork_idir_update.atworkidirupdateadminsettings');

    // Setup the select field
    // We need to grab all available user fields, so we can add them to a dropdown
    $user_fields = $this->getFillableFields();
    $values = [
      'None' => 'None',
    ];
    // Add all field names to dropdown, for mapping.
    foreach($user_fields as $key => $value){
      $values[$key] = t($key);
    }

    // Function to grab just the .csv labels and return them
    $column_names = $this->getColumnNames();

    // Foreach the labels, then add them to the fieldset
    // csv columns as labels, while the $user_fields will be added to a dropdown.
    foreach($column_names as $name){
      $form['names_fieldset'][$name] = [
        '#type' => 'select',
        '#title' => $this->t($name),
        '#description' => $this->t('Choose field mapping'),
        '#options' => $values,
      ];
    }

    return $form['names_fieldset'];
  }

  public function idirValidateFields(array &$form, FormStateInterface $form_state) {
    if($form_state->isValueEmpty('idir_ftp_location') == TRUE){
      $form_state->setErrorByName('[idir_ftp_location]', $this->t('You must enter an FTP address'));
    }
    if($form_state->isValueEmpty('idir_login_name') == TRUE){
      $form_state->setErrorByName('[idir_login_name]', $this->t('You must enter a login name'));
    }
    if($form_state->isValueEmpty('idir_login_password') == TRUE){
      $form_state->setErrorByName('[idir_login_password]', $this->t('You must enter a password'));
    }
  }

  /**
   * Helper function to create an array of user fields that we ccan expose to the admin, so they can map .csv entries.
   * @return array $user_fields A collection of user fields after removeing the ones we shouldn't expose to the user.
   */
  public function getFillableFields(){
    // Grab all useable user fields
    $fields = \Drupal::service('entity_field.manager')->getFieldMap('user');
    $user_fields = $fields['user'];
    $fields = null;
    // We want to weed out the fields we definitely don't want to mess with
    $default_fields = [
      'uid',
      'uuid',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode',
      'timezone',
      'status',
      'created',
      'changed',
      'access',
      'roles',
      'default_langcode',
      'path',
      'message_subscribe_email',
      'message_digest'
    ];
    foreach($default_fields as $key){
      //ksm($key);
      if(array_key_exists($key, $user_fields)){
        unset($user_fields[$key]);
        //ksm($user_fields);
      }
    }
    return($user_fields);
  }
  public function getColumnNames(){
     $csv = [];
    // If we have a current .csv, we can use that
    $timestamp = date('Ymd');
    $exists = file_exists('public://idir/' . $timestamp ."/idir_" . $timestamp . ".tsv");
    if($exists){
      // We have a file, grab the first row and return it
      $handle = fopen('public://idir/' . $timestamp ."/idir_" . $timestamp . ".tsv", "r");
      $csv = fgetcsv($handle, '', "\t");
      fclose($handle);
    } else {
      // Else we need to fire the controller so we can pull one down, and then we can check again.
      $new_file = new AtworkIdirUpdateController;
      $generate_csv = $new_file->AtworkIdirInit();
      if( file_exists('public://idir/' . $timestamp ."/idir_" . $timestamp . ".tsv")){
        // now grab and add it - or throw an error and end.
        $handle = fopen('public://idir/' . $timestamp ."/idir_" . $timestamp . ".tsv", "r");
        $csv = fgetcsv($handle, '', "\t");
        fclose($handle);
      } else {
        // throw an error
        \Drupal::logger('atwork_idir_update')->error('Cannot access or download idir.csv file from location. Please check URL/User and Password and try again.');
        drupal_set_message("Cannot access or download idir.csv, no fields to generate. Please check credentials and try again.");
      }
    }

    return $csv;
  }
}
