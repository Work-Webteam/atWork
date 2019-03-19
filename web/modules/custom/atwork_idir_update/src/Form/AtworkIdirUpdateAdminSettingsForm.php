<?php

namespace Drupal\atwork_idir_update\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
    // Check for idir stuff, and generate fields if they are available
    if($config->get('idir_ftp_location') != '' &&  $config->get('idir_login_name') != '' && $config->get('idir_login_password') != ''){
      // Add in our other fields - we can now reach out and pull the csv
      $this->idirGenerateFields($form, $form_state);
    }
    //TODO: Set our own Validation to make sure values for the idir scel are unique.
    $form['#validate'][] = [$this, "idirValidateFields"];
    //ksm($form);
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
    $this_form = $form_state->getUserInput();
    $config = $this->config('atwork_idir_update.atworkidirupdateadminsettings');
    foreach($this_form as $key=>$value){
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    // TODO: Gather all additional fields (if any) and save from form-state dropdown (from fieldset).
    //$this->$set_form;
    /*
    $this->config('atwork_idir_update.atworkidirupdateadminsettings')
      ->set('idir_ftp_location', $form_state->getValue('idir_ftp_location'))
      ->set('idir_login_name', $form_state->getValue('idir_login_name'))
      ->set('idir_login_password', $form_state->getValue('idir_login_password'))
      ->save();
    */

  }


  // LABEL == column name, dropdown = available user fields.
  public function idirGenerateFields(array &$form, FormStateInterface $form_state){
    // Setup the select field
    // We need to grab all available user fields, so we can add them to a dropdown
    $user_fields = $this->getFillableFields();
    $values = [
      'action' => 'action',
    ];
    // Add all field names to dropdown, for mapping.
    foreach($user_fields as $key => $value){
      $values[$key] = t($key);
    }

    // Function to grab just the .csv labels and return them
    $column_names = $this->getColumnNames();
    // Make these strings rather than int arrays
    foreach($column_names as $label => $label_value){
      $column_names[$label_value] = $label_value;
      unset($column_names[$label]);
    }
    // Need a none option if we don't want to set a field
    $column_names['None'] = 'None';
    $config = $this->config('atwork_idir_update.atworkidirupdateadminsettings');
    // csv columns as labels, while the $user_fields will be added to a dropdown.
    //ksm($values);

    foreach($values as $name=>$filed_value) {
      // TODO: Check if this field exists - if so, we update rather than add. Else we add and create the field for the form.
      if (isset($form[$name])) {

      }
      else {

        $form[$name] = [
          '#type' => 'select',
          '#title' => $this->t($name),
          '#description' => $this->t('Choose field mapping'),
          '#options' => $column_names,
          '#default_value' => $config->get($name),
        ];
      }
    }
    $form_state->setRebuild(TRUE);
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
    // We need to have at least on "Action" column, and it should contain specific commands
    $this_form = $form_state->getUserInput();
    if(!array_key_exists("action", $this_form) || $this_form["action"] == "None"){
      $form_state->setErrorByName('[TransactionType]', $this->t('You must assign action to one of the provided user record fields. This field should include one of three actions - "Add" "Modify" or "Delete". Without these directives, the module will not be able to act on the records.'));
    }
    // We need to have a primary key - This should be GUID, so GUID should be assigned to one of the labels.
    if(!in_array("GUID", $this_form)){
      $form_state->setErrorByName('[GUID]', $this->t('As part of your .tsv import, you must have a unique identifier. When this module was written, only GUID could be used for this purpose. Therefore, any .csv or .tsv that is pulled in must contain this column for every record, and it should be labelled GUID. If this is no longer the case, this module will need to be patched to use a new primary key.'));
    }

    //  We can't have more than one field mapped to any one label, unless that field is None
    foreach($this_form as $key=>$value){
      if($value == "None"){
        unset($this_form[$key]);
      }
      // Init also uses email - so we need to skip this in our check, or we will not have unique values in every field
      if($key == "init" || $key == "name"){
        unset($this_form[$key]);
      }
    }
    if(count($this_form) != count(array_unique($this_form))){
      $form_state->setErrorByName('', $this->t('You may not assign more than one column label to a field. Please make sure you have not assigned a field to more than one import column.'));
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
      if(array_key_exists($key, $user_fields)){
        unset($user_fields[$key]);
      }
    }
    return($user_fields);
  }


  /**
   * Helper method that gathers and returns the column labels in a csv field.
   * @return array|false|null
   * @throws \exception
   */
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
