<?php

/**
 * @file
 * Contains \Drupal\siteminder\Form\SettingsForm.
 */

namespace Drupal\siteminder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the simplesamlphp_auth basic settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'siteminder_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['siteminder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('siteminder.settings');
    $form['general'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
      '#collapsible' => FALSE,
    );
    // ID mapping
    $id = (empty($config->get('user.id_mapping'))) ? "smgov_userguid" : $config->get('user.id_mapping');
    $form['general']['id_mapping'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Which variable from siteminder should be used as ID"),
      '#default_value' => $id,
      '#description' => $this->t('The header field that maps to a unique identifier for a given user.'),
      '#required' => TRUE,
    );
    $guid = (empty($config->get('user.guid_mapping'))) ? "username" : $config->get('user.guid_mapping');
    $form['general']['guid_mapping'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Which field would you like to map the siteminder ID to?"),
      '#default_value' => $guid,
      '#description' => $this->t('The drupal field that will be used to store and verify the ID variable chosen above.'),
      '#required' => TRUE,
    );
    // Username mapping
    $username = (empty($config->get('user.username_mapping'))) ? "smgov_userdisplayname" : $config->get('user.username_mapping');
    $form['general']['username'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Username mapping configuration'),
      '#collapsible' => FALSE,
    );
    $form['general']['username']['username_form'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Check if you want the user to manually enter his first name and last name."),
      '#default_value' => $config->get('user.username_form'),
      '#description' => $this->t("After being connected for the first time, the user will be redirected to a form to enter/update the first name, last name and email fields. The variable is necessary for the user display name field."),
    );
    $form['general']['username']['username_duplicate'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("To avoid duplicate user with the same usernames, store the GUID as username."),
      '#default_value' => $config->get('user.username_duplicate'),
      '#description' => $this->t("If checked, it will store the Siteminder GUID."),
    );
    $form['general']['username']['username_mapping'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Which variable from siteminder should be used as user's name"),
      '#default_value' => $username,
      '#description' => $this->t('Define the name of the variable that your Siteminder configuration will use to pass the authenticated user name.'),
      '#required' => TRUE,
    );
    $form['general']['username']['username_mapping_field'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Which field in Drupal should the above header map to"),
      '#default_value' => $config->get('user.username_mapping_field'),
      '#description' => $this->t("This is will save the above header into a field without formatting it."),
    );
    // Email mapping
    $email = (empty($config->get('user.mail_mapping'))) ? "smgov_useremail" : $config->get('user.mail_mapping');
    $form['general']['email'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Email mapping configuration'),
      '#collapsible' => FALSE,
    );
    $form['general']['email']['email_required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check if you want to configure email address as a non required field'),
      '#default_value' => $config->get('user.email_required'),
      '#description' => $this->t("If checked, new users without email address won't have error message"),
    );
    $form['general']['email']['email_mapping'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Which variable from siteminder should be used as user mail address'),
      '#default_value' => $email,
      '#description' => $this->t('Define the name of the variable that your Siteminder configuration will use to pass the email address.'),
    );
    $form['general']['email']['sync_everytime'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check if you want to sync the username and email on every login'),
      '#default_value' => $config->get('user.sync_everytime'),
      '#description' => $this->t("For use if you changed settings around usernames and emails and want the changes to affect existing users."),
    );
    // User DN mapping
    $dn = (empty($config->get('user.dn_mapping'))) ? "smuserdn" : $config->get('user.dn_mapping');
    $form['general']['dn'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('User DN mapping configuration'),
      '#collapsible' => FALSE,
    );
    $form['general']['dn']['dn_required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check if you want to configure user DN variable as a non required field'),
      '#default_value' => $config->get('user.dn_required'),
      '#description' => $this->t("If checked, the module will authorize new users that don't belong to authorized group list to register."),
    );
    $form['general']['dn']['dn_mapping'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Which variable from siteminder should be used as User DN"),
      '#default_value' => $dn,
      '#description' => $this->t('The header field that maps to the distinguished name of the user.'),
    );
    // AUthorized groups list
    $auth_groups_defined = 'Health;Children and Families;Vital Statistics Agency;Mental Health and Addictions';
    $auth_groups = (empty($config->get('user.auth_group'))) ? $auth_groups_defined : $config->get('user.auth_group');

    $form['general']['auth_group'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Authorized group list'),
      '#default_value' => $auth_groups,
      '#description' => $this->t('Separate each group with a ";". Enter ; to allow all IDIRs access.'),
    );
    // Usertype mapping
    $usertype = (empty($config->get('user.usertype_mapping'))) ? "smgov_usertype" : $config->get('user.usertype_mapping');
    $form['general']['usertype_mapping'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Which variable from siteminder should be used as User type"),
      '#default_value' => $usertype,
      '#description' => $this->t('The type of user that was authenticated.'),
    );
    $form['general']['username_prefix'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Strip prefix'),
      '#default_value' => $config->get('user.prefix_strip'),
      '#description' => $this->t('Enable this if your Siteminder configuration adds a prefix to the username and you do not want it used in the username.'),
    );
    $form['general']['username_domain'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Strip domain'),
      '#default_value' => $config->get('user.domain_strip'),
      '#description' => $this->t('Enable this if your Siteminder configuration adds a domain to the username and you do not want it used in the username.'),
    );
    $form['general']['role_mapping'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Automatic role population from Siteminder variable'),
      '#default_value' => $config->get('user.role_mapping'),
      '#description' => $this->t('A list of rules. One drupal role per line. Each rule consists of a Drupal role id, a siteminder variable name, an operation and a value to match. <i>example:<br />role_id1:attribute_name|operation|value<br />role_id2:attribute_name2|operation|value;attribute_name3|operation|value</i><br /><br />Each operation may be either "@", "@=" or "~=". <ul><li>"=" requires the value exactly matches the attribute;</li><li>"@=" requires the portion after a "@" in the attribute to match the value;</li><li>"~=" allows the value to match any part of any element in the attribute array.</li></ul>For instance:<br /><i>staff:eduPersonPrincipalName|@=|uninett.no;affiliation|=|employee<br />admin:mail|=|andreas@uninett.no</i><br />would ensure any user with an eduPersonPrinciplaName siteminder variable matching .*@uninett.no would be assigned a staff role and the user with the mail attribute exactly matching andreas@uninett.no would assume the admin role.'),
    );
    $form['general']['role_evaluate_everytime'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Re-evaluate roles every time the user logs in'),
      '#default_value' => $config->get('user.role_evaluate_everytime'),
      '#description' => $this->t('NOTE: This means users could lose any roles that have been assigned manually in Drupal.'),
    );
    $form['general']['siteminder_cookie'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Provide the Siteminder Client Side Cookie Name'),
      '#default_value' => $config->get('siteminder_cookie'),
      '#required' => TRUE,
      '#description' => $this->t('Specify a Siteminder Cookie Name set on the client side.'),
    );
    $form['general']['new_user_validation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check if you want a manual activation of new users'),
      '#default_value' => $config->get('user.new_user_validation'),
      '#description' => $this->t('If checked, new users will have the status "blocked" by default'),
    );
    $form['general']['pending_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Message displayed on the Pending Validation page'),
      '#default_value' => $config->get('user.pending_message'),
      '#required' => TRUE,
      '#description' => $this->t('Message displayed on the page /pending_validation'),
    );
    // Specific configuration if you don't want to enable Siteminder on a specifix URL
    $form['general']['exclude_url'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Exclude a specific URL from Siteminder'),
      '#collapsible' => FALSE,
    );
    $form['general']['exclude_url']['disable_url'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check if you want to exclude a specific URL from Siteminder'),
      '#default_value' => $config->get('user.disable_url'),
      '#description' => $this->t('NOTE: If checked, the URL entered below will be excluded from Siteminder'),
    );
    $form['general']['exclude_url']['excluded_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter the URL'),
      '#default_value' => $config->get('user.excluded_url'),
    );
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('siteminder.settings');

    $config->set('user.username_mapping', $form_state->getValue('username_mapping'));
    $config->set('user.username_mapping_field', $form_state->getValue('username_mapping_field'));
    $config->set('user.id_mapping', $form_state->getValue('id_mapping'));
    $config->set('user.guid_mapping', $form_state->getValue('guid_mapping'));
    $config->set('user.username_form', $form_state->getValue('username_form'));
    $config->set('user.username_duplicate', $form_state->getValue('username_duplicate'));
    $config->set('user.dn_required', $form_state->getValue('dn_required'));
    $config->set('user.dn_mapping', $form_state->getValue('dn_mapping'));
    $config->set('user.email_required', $form_state->getValue('email_required'));
    $config->set('user.usertype_mapping', $form_state->getValue('usertype_mapping'));
    $config->set('user.mail_mapping', $form_state->getValue('email_mapping'));
    $config->set('user.sync_everytime', $form_state->getValue('sync_everytime'));
    $config->set('user.auth_group', $form_state->getValue('auth_group'));
    $config->set('user.prefix_strip', $form_state->getValue('username_prefix'));
    $config->set('user.domain_strip', $form_state->getValue('username_domain'));
    $config->set('user.role_mapping', $form_state->getValue('role_mapping'));
    $config->set('user.role_evaluate_everytime', $form_state->getValue('role_evaluate_everytime'));
    $config->set('siteminder_cookie', $form_state->getValue('siteminder_cookie'));
    $config->set('user.new_user_validation', $form_state->getValue('new_user_validation'));
    $config->set('user.pending_message', $form_state->getValue('pending_message'));
    $config->set('user.disable_url', $form_state->getValue('disable_url'));
    $config->set('user.excluded_url', $form_state->getValue('excluded_url'));
    $config->save();
  }

}
