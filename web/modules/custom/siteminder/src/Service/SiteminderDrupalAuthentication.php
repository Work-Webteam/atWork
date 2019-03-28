<?php

/**
 * @file
 * Contains \Drupal\siteminder\Service\SiteminderDrupalAuthentication.
 */

namespace Drupal\siteminder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\externalauth\ExternalAuthInterface;

/**
 * After Successful login with Siteminder authentication with Drupal users.
 */
class SiteminderDrupalAuthentication {

  /**
   * Siteminder helper.
   *
   * @var siteminder
   */
  protected $siteminder;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The ExternalAuth service.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalauth;

  /**
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   *
   * @param Siteminder $siteminder_info
   *   The Siteminder helper service.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param ExternalAuthInterface $externalauth
   *   The ExternalAuth service.
   * @param AccountInterface $account
   *   The currently logged in user.
   */
  public function __construct(Siteminder $siteminder_info, ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, ExternalAuthInterface $externalauth, AccountInterface $account) {
    $this->siteminder = $siteminder_info;
    $this->config = $config_factory->get('siteminder.settings');
    $this->entityManager = $entity_manager;
    $this->externalauth = $externalauth;
    $this->currentUser = $account;
  }

  /**
   * Log in and optionally register a user based on the authname provided.
   *
   * @param string $authname
   *   The authentication name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The logged in Drupal user.
   */
  public function externalLoginRegister($authname) {

    // TODO: We need to use a username to check a login. But we are using a GUID to authenticate, so we need to use the GUID to authenticate vs. guid field, then if they match check username/sm_user name. If these both checkout then we can continue.
    $account = $this->externalauth->login($authname, 'siteminder');
    $sm_authname = $this->config->get('user.username_mapping');
    $sm_headers = $this->siteminder->getSiteminderHeaderVariables();
    $name = $this->siteminder->getSiteminderUsername($sm_authname, $sm_headers);

    if ($account) {
      // Determine if roles should be evaluated upon login.
      if ($this->config->get('user.role_evaluate_everytime')) {
        $this->roleMatchAdd($account);
      }
      // Check if the account needs to be updated
      /* we don't want to update
      if (($account->getEmail() != $this->siteminder->getDefaultEmail()) || ($account->getAccountName() != $name)) {
        $this->synchronizeUserAttributes($account, $sm_authname, $sm_headers, true, false);
      }
      */
    }

    return $account;
  }

  /**
   * Registers a user locally as one authenticated by the Siteminder.
   *
   * @param string $authname
   *   The authentication name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|bool
   *   The registered Drupal user.
   *
   * @throws \Exception
   *   An ExternalAuth exception.
   */
  public function externalRegister($authname) {
    $account = FALSE;
    $sm_authname = $this->config->get('user.username_mapping');
    $sm_headers = $this->siteminder->getSiteminderHeaderVariables();
    $name = $this->siteminder->getSiteminderUsername($sm_authname, $sm_headers);

    /** Check whether the user with this authname
     *  already exists in the Drupal database.
     */
    // Right here, we need to check the id ($authname) vs. the field we set the mapping to.
    $id_field = $this->config->get('user.guid_mapping');
    $existing_user = $this->entityManager->getStorage('user')->loadByProperties(array($id_field => $authname));
    $existing_user = $existing_user ? reset($existing_user) : FALSE;
    if ($existing_user) {
      $this->externalauth->linkExistingAccount($authname, 'siteminder', $existing_user);
      $account = $existing_user;
    }
    // TODO: We don't want to make a new account, maybe we shoudl bump the error up higher.
    if (!$account) {
      // New user manual validation settings
      $status = ($this->config->get('user.new_user_validation')) ? 0 : 1;
      // Create the new user.
      try {
        $account = $this->externalauth->register($authname, 'siteminder', array(), NULL, $name, $status);
      } catch (\Exception $ex) {
        watchdog_exception('siteminder', $ex);
        drupal_set_message(t('Error registering user: An account with this username already exists.'), 'error');
      }
    }

    if ($account) {
      $this->roleMatchAdd($account);
      // We don't want ot update this stuff
      //$this->synchronizeUserAttributes($account, $sm_authname, $sm_headers, true, true);
      return $this->externalauth->userLoginFinalize($account, $authname, 'siteminder');
    }
  }

  /**
   * Synchronizes user data if enabled.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The Drupal account to synchronize attributes on.
   * @param bool $force
   *   Define whether to force syncing of the user attributes, regardless of
   *   SimpleSAMLphp settings.
   */
  public function synchronizeUserAttributes(AccountInterface $account, $sm_authname, $sm_headers, $force = FALSE, $creation = TRUE) {
    if ($this->config->get('user.sync_everytime')) {
      $force = TRUE;
      $creation = TRUE;
    }
    $mail_mapping = $force || $this->config->get('user.mail_mapping');
    $sync_user_name = $force || $this->config->get('user.username_mapping');
    $sync_display_name = $force || $this->config->get('user.username_mapping_field');
    $complete_name = $this->siteminder->getSiteminderUsername($sm_authname, $sm_headers);

    // Username
    if ($sync_user_name && $creation) {
      $name = $this->siteminder->getSiteminderUsername($sm_authname, $sm_headers);
      if ($name) {
        $existing = FALSE;
        $account_search = $this->entityManager->getStorage('user')->loadByProperties(array('name' => $name));
//        if ($existing_account = reset($account_search)) {
//          if ($this->currentUser->id() != $existing_account->id()) {
//            $existing = TRUE;
//            drupal_set_message(t('Error synchronizing username: an account with this username already exists.'), 'error');
//          }
//        }

        if (!$existing) {
          $account->setUsername($name);
        }
      } else {
        drupal_set_message(t('Error synchronizing username: no username is provided by SAML.'), 'error');
      }
    }

    // Email
    if ($this->config->get('user.username_form')) {
      if ($creation) {
        $mail = $this->siteminder->getDefaultEmail();
        if ($mail) {
          $account->setEmail($mail);
        }
      }
    } elseif ($mail_mapping) {
      $mail = $this->siteminder->getDefaultEmail();
      if ($mail) {
        $account->setEmail($mail);
      } else {
        if (!$this->config->get('user.email_required')) {
          drupal_set_message(t('Error synchronizing mail: no email address is provided by SAML.'), 'error');
        }
      }
    }
    
    // Display Name
    if ($this->config->get('user.username_mapping_field') && $creation) {
      $user = \Drupal\user\Entity\User::load($account->id());
      $display_name = $this->siteminder->getSiteminderHeaderVariable($this->config->get('user.username_mapping'));

      if ($user->hasField($this->config->get('user.username_mapping_field')) && $display_name) {
        $account->set($this->config->get('user.username_mapping_field'), $display_name);
      }
    }

    // Update fist name and last name
    // If the user has to manually enter his first name and last name, assign a temporary fist name and last name
    // Else use the username variable to get the first name and last name.
    if ($this->config->get('user.username_form') && $creation) {
      $username = "";
      $username = $this->siteminder->getSiteminderHeaderVariable($this->config->get('user.username_mapping'));
      $account->set('field_first_name', "temp_" . $username);
      $account->set('field_last_name', "temp_" . $username);
    } elseif ($complete_name && !$this->config->get('user.username_form')) {
      $account->set('field_first_name', $complete_name[1]);
      $account->set('field_last_name', trim(preg_replace("/[a-zA-Z]{2}\:/", "", $complete_name[0])));
    }

    // Update original email address field value retrieved from Siteminder
    if ($account->hasField('field_sm_email')) {
      $sm_email = $this->siteminder->getDefaultEmail();
      $account->set('field_sm_email', $sm_email);
    }

    // Update user type field value retrieved from Siteminder
    if ($account->hasField('field_sm_usertype')) {
      $sm_usertype = $this->siteminder->getUserType();
      $account->set('field_sm_usertype', $sm_usertype);
    }

    if ($mail_mapping || $sync_user_name || $sync_display_name) {
      $account->save();
    }
  }


  /**
   * Adds roles to user accounts.
   *
   * @param UserInterface $account
   *   The Drupal user to add roles to.
   */
  public function roleMatchAdd(UserInterface $account) {
    // Get matching roles based on retrieved SimpleSAMLphp attributes.
    // $managed_roles are all roles that have a role map entry
    // $matched_roles are only the roles the user is mapped to
    //$matching_roles = $this->getMatchingRoles();
    list($managed_roles, $matched_roles) = $this->getMatchingRoles();
    if ($managed_roles) {
      foreach ($managed_roles as $role_id) {
        if (in_array($role_id, $matched_roles)) {
          $account->addRole($role_id);
        }
        else {
          $account->removeRole($role_id);
        }
      }
      $account->save();
    }
  }

  /**
   * Get matching user roles to assign to user.
   *
   * Matching roles are based on retrieved SimpleSAMLphp attributes.
   *
   * @return array
   *   List of matching roles to assign to user.
   */
  public function getMatchingRoles() {
    $managed_roles = array();
    $matched_roles = array();
    // Obtain the role map stored. The role map is a concatenated string of
    // rules which, when SimpleSAML attributes on the user match, will add
    // roles to the user.
    // The full role map string, when mapped to the variables below, presents
    // itself thus:
    // $role_id:$key|$op|$value;$key|$op|$value
    // $role_id2:$key|$op|$value etc.
    if ($rolemap = $this->config->get('user.role_mapping')) {
      foreach (explode("\n", str_replace(["\r\n", "\n\r", "\r"], "\n", $rolemap)) as $rolerule) {
        //foreach (explode('|', $rolemap) as $rolerule) {
        list($role_id, $role_eval) = explode(':', $rolerule, 2);
        $managed_roles[$role_id] = $role_id;

        foreach (explode(';', $role_eval) as $role_eval_part) {
          if ($this->evalRoleRule($role_eval_part)) {
            $matched_roles[$role_id] = $role_id;
          }
        }
      }
    }
    return [$managed_roles, $matched_roles];
  }

  /**
   * Determines whether a role should be added to an account.
   *
   * @param string $role_eval_part
   *   Part of the role evaluation rule.
   *
   * @return bool
   *   Whether a role should be added to the Drupal account.
   */
  protected function evalRoleRule($role_eval_part) {
    list($variable_key, $operator, $value) = explode('|', $role_eval_part);
    $header_variables = $this->siteminder->getSiteminderHeaderVariables();
    if (!array_key_exists($variable_key, $header_variables)) {
      return FALSE;
    }
    $match_variable = $header_variables[$variable_key];
    // A '=' requires the $value exactly matches the $attribute, A '@='
    // requires the portion after a '@' in the $attribute to match the
    // $value and a '~=' allows the value to match any part of any
    // element in the $attribute array.
    switch ($operator) {
      case '=':
        return ($value == $match_variable);

      case '@=':
        list($before, $after) = explode('@', array_shift($match_variable));
        return ($after == $value);

      case '~=':
        return strpos($match_variable, $value) !== FALSE;
    }
  }

}
