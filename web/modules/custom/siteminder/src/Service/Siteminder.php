<?php

/**
 * @file
 * Contains \Drupal\siteminder\Service\Siteminder.
 */

namespace Drupal\siteminder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Service to check the Siteminder header information.
 */
class Siteminder {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('siteminder.settings');
  }

  /**
   * Check whether user is authenticated and user information is available in the Header
   *
   */
  public function isAuthenticated() {
    return $this->getAuthname();
  }

  /**
   * Gets the unique Mapping form the Header.
   *
   * @return string
   *   The authname.
   */
  public function getAuthname() {
    return $this->getSiteminderHeaderVariable($this->config->get('user.username_mapping'));
  }

  /**
   * Gets the unique Mapping form the Header.
   *
   * @return string
   *   The authname.
   */
  public function getId() {
    return $this->getSiteminderHeaderVariable($this->config->get('user.id_mapping'));
  }

  /**
   * Gets the field that the user mapped their siteminder id field to
   * @return string
   * The drupal field that holds the users GUID
   */
  public function getDrupalIdField(){
    return $this->config->get('user.guid_mapping');
  }

  /**
   * Gets the name attribute.
   *
   * @return string
   *   The name attribute.
   */
  public function getDefaultName() {
    return $this->getSiteminderHeaderVariable($this->config->get('user.username_mapping'));
  }

  /**
   * Gets the mail attribute.
   *
   * @return string
   *   The mail attribute.
   */
  public function getDefaultEmail() {
    return $this->getSiteminderHeaderVariable($this->config->get('user.mail_mapping'));
  }

  /**
   * Gets the user type.
   *
   * @return string
   *   The mail attribute.
   */
  public function getUserType() {
    return $this->getSiteminderHeaderVariable($this->config->get('user.usertype_mapping'));
  }

  /**
   * Get a specific Siteminder Variable from the Header.
   *
   */
  public function getSiteminderHeaderVariables() {
    // If the Siteminder ID is not present in the HTTP header, stop the process
    $get_all_headers = getallheaders();
    $unique_variable = $this->config->get('user.id_mapping');
    $request_server = \Drupal::request()->server->get($unique_variable);
    if (isset($get_all_headers[$unique_variable])) {
      return $get_all_headers;
    } elseif (!empty($request_server)) {
      return \Drupal::request()->server->all();
    }
  }

  /**
   * Get a specific Siteminder Variable from the Header and return the matached attribute.
   *
   */
  public function getSiteminderHeaderVariable($variable) {
    $header_variables = $this->getSiteminderHeaderVariables();
    if (isset($variable)) {
      if (!empty($header_variables[$variable])) {
        return $header_variables[$variable];
      }
    }
  }

  /**
   * Let's Check to see if the user is allowed to login
   * All users are allowed to see the site, but let's only log in users from the groups identified
   *
   */
  public function checkUserDN() {
    $user_check = FALSE;
    $sm_userdn = explode(",OU=", $this->getSiteminderHeaderVariable($this->config->get('user.dn_mapping')));
    $allowed_values = $this->getAuthGroups();

    if ($allowed_values == ';') {
      return TRUE;
    }

    // Check the user's OU value
    foreach ($sm_userdn as $key => $value) {
      if (in_array($value, $allowed_values)) {
        $user_check = TRUE;
        break;
      }
    }

    return $user_check;
  }

  /**
   * Retrieve the authorized groups list configured
   * Format data to return an array
   *
   * @return array
   *
   */
  private function getAuthGroups() {
    $group_list = (empty($this->config->get('user.auth_group'))) ? array() : $this->config->get('user.auth_group');
    if ($group_list == ';')
      return $group_list;
    if (!empty($group_list)) {
      $group_list_array = array();
      $group_list_array = explode(";", $group_list);
      return $group_list_array;
    }

    return $group_list;
  }

  /**
   * Check if an email is present in the HTTP header variables
   *
   */
  public function checkEmptyEmail() {
    $email = $this->getDefaultEmail();

    return (empty($email)) ? TRUE : FALSE;
  }

  /**
   * Check if an uid exists in the siteminder table
   *
   * @param string $id
   *   The Header value for the given key
   *
   * @return fetch data
   *
   */
  public function getUid($id) {
    $connection = \Drupal::database();
    $result = $connection->query("SELECT uid FROM {authmap} WHERE authname = :id", [
      ':id' => $id,
    ]);

    return $result->fetchField();
  }

  /**
   * Check if the email address exists in the database
   *
   * @param string $email
   *   The Header value for the email
   *
   * @return fetch data
   *
   */
  public function checkEmail($email) {
    $connection = \Drupal::database();
    $result = $connection->query("SELECT sid FROM {siteminder} WHERE sid = :sid", [
      ':sid' => $this->getId(),
    ]);

    return $result->fetchField();
  }

  /**
   * Assemble a username based on selected header values.
   *
   * @param $map The active siteminder map
   * @param $headers The active headers the user is sending
   *
   * @return string - an assembled username based on space-separated concatenation of selected header values.
   */
  public function getUserName($map, $headers) {
    $string = $map;
    // If our map is only a string, there is no reason to go through all of this
    // If we swap out the name variable from idir, we may need to remove this.
    if(gettype($map) == "string"){
      return $map;
    }
    if (strpos($string, "\n") !== FALSE) {
      $string = explode("\n", $string);
      $parts = array();
      foreach ($string as $field) {
        $parts[] = $headers[trim($field)];
      }
      return implode(" ", $parts);
    } else {
      //return $headers[$string];
      $name_stripped = explode(', ', preg_replace("/\s[a-zA-Z]*\:[a-zA-Z]{2}/", "", $headers[$string]));
      // Check and format if IDIR ID is still in the name
      if (preg_match('/(.*)\((.*?)\)(.*)/', $name_stripped[1])) {
        preg_match('/(.*)\((.*?)\)(.*)/', $name_stripped[1], $match);
        $name_stripped[1] = str_replace(' ', '', $match[1]);
      }
      return $name_stripped;
    }
  }

  /**
   * Assemble a username based on selected header values.
   *
   * @param $map The active siteminder map
   * @param $headers The active headers the user is sending
   *
   * @return string - an assembled username based on space-separated concatenation of selected header values.
   */
  public function formatUserName($map, $headers) {
    $name_stripped = $this->getUserName($map, $headers);
    return trim($name_stripped[1]) . ' ' . trim(preg_replace("/[a-zA-Z]{2}\:/", "", $name_stripped[0]));
  }

  /**
   * Manage error cases
   *
   * @return boolean
   */
  public function manageErrorCases() {
    $error = false;
    $email = $this->config->get('user.email_required');
    // If no SM ID in the HTTP header
    if (!$this->getId()) {
      drupal_set_message(t('Unable to create an account, the USER GUID is missing'), 'error');
      return $error = true;
    }
    // If no SM Email in the HTTP header
    elseif (!$this->getDefaultEmail()) {
      if (!$this->config->get('user.email_required')) {
        drupal_set_message(t('Unable to create an account, the USER Email is missing'), 'error');
        return $error = true;
      }
    }
    // If no SM Username in the HTTP header
    elseif (!$this->getAuthname()) {
      if (!$this->config->get('user.username_form')) {
        drupal_set_message(t('Unable to create an account, the USER Name is missing'), 'error');
        return $error = true;
      }
    }
    // If the user doesn't belong to the right group
    elseif (!$this->checkUserDN()) {
      if (!$this->config->get('user.dn_required')) {
        drupal_set_message(t('These is something wrong with your user profile, please contact web services to resolve this issue.  Unable to login at this point'), 'error');
        return $error = true;
      }
    }

    return $error;
  }

  /**
   * Check if the domain is authorized or excluded from Siteminder
   *
   * @return boolean
   */
  public function checkAuthDomain() {
    $auth = true;
    if ($this->config->get('user.disable_url')) {
      $domain = \Drupal::request()->getSchemeAndHttpHost();
      $domain_config = $this->config->get('user.excluded_url');
      if ($domain == $domain_config) {
        $auth = false;
      }
    }

    return $auth;
  }

  /**
   * Get the username from Siteminder
   *
   * @return String
   *
   */
  public function getSiteminderUsername($sm_authname, $sm_headers) {
    $username = "";

    // Store the username without any formatting
    if ($this->config->get('user.username_duplicate')) {
      $username = substr(preg_replace("/[^A-Za-z0-9]/", ".", $this->getId()), 0, 59);
    } else {
      $username = $this->formatUserName($sm_authname, $sm_headers);
    }

    // Security if the variable is empty --> create a username with GUID
    if (empty($username)) {
      $username = "user_" . $this->getId();
    }

    return $username;
  }

}
