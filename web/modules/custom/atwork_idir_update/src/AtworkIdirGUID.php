<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

class AtworkIdirGUID
{
  protected $timestamp;
  protected $drupal_path;
  
  function __construct()
  {
    // Use timestamp and drupal_path mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    // TODO: Should these be going into the Public:// file folder?
    $this->drupal_path = $this->getModulePath('atwork_idir_update');
  }

  protected function getModulePath($moduleName)
  {
    return drupal_get_path('module', $moduleName);
  }

    /** 
   * This function takes a GUID input, then returns user number (if the user is in our system) or else false if not in our system
   */
  public function getGUIDField($guid)
  {
    $connection = \Drupal::database();
    $result = $connection->select('user__field_guid', 'fg')
      ->fields('fg', array('entity_id'))
      ->distinct(true)
      ->condition("fg.field_guid_value", $guid, '=')
      ->execute()->fetchCol();
      return $result;
  }

    /**
   * updateSystemUser: This function makes any necessary updates to our db for the user. By the time the user gets here, we know we need to make some changes. Shared by add/delete and update methods. Once successful, send a note to the success method and return
   *
   * @param [string] $type : This denotes if this is an update or a delete
   * @param  [string] $uid : userid we found when checking for a user - can load with this
   * @param [array] $fields : An array of fields we need for the user.  
   * @return void
   */
  public function updateSystemUser($type, $uid, $fields)
  {
    // User fields are updated with new info, and user is saved.
    if($type == 'add')
    {
      $this_user = User::create();
    }
    else
    {
      $this_user = User::load($uid);
    }
    $this_user->set('init', $fields['4']);
    $this_user->setUsername($fields['2']);
    $this_user->setPassword($fields['1']);
    $this_user->setEmail($fields['4']);
    // TODO: add in other user fields here.
    $this_user->set('field_guid', $fields['1']);
    $this_user->set('field_display_name', $fields['3']);
    $this_user->set('field_given_name', $fields['5']);
    $this_user->set('field_surname', $fields['6']);
    $this_user->set('field_phone', $fields['7']);
    $this_user->set('field_title', $fields['8']);
    $this_user->set('field_department', $fields['9']);
    $this_user->set('field_office', $fields['10']);
    $this_user->set('field_organization_code', $fields['11']);
    $this_user->set('field_company', $fields['12']);
    $this_user->set('field_street', $fields['13']);
    $this_user->set('field_city', $fields['14']);
    $this_user->set('field_province', $fields['15']);
    $this_user->set('field_postal_code', $fields['16']);

    // We need this to for sure be a new user - we don't want to edit an existing user.
    if($type == 'add')
    {
      $this_user->enforceIsNew(true);
    }
    if($type ==  'delete')
    {
      // This unpublishes their account
      $this_user->block();
    }
    else
    {
      // This publishes their account.
      $this_user->activate();
    }
    // Save user
    $result = $this_user->save();

    return $result;
  }
}