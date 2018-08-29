<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;
/**
 * This class is the parent of the parse classes. We deal with updating users and checking users here, but this class won't be invoked on its own.
 */
class AtworkIdirGUID
{
  protected $timestamp;
  protected $drupal_path;
  // This is a user object that can be set with an array of user info, generally used by child classes and passed back to the updateSystemUser() method
  protected $new_fields = [];
  
  function __construct()
  {
    // Use timestamp and drupal_path mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    // grab the path to the Public:// file folder
    $this->drupal_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/';
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
    if( $type == 'add' ){
      $this_user = User::create();
      // If this is a new user - we have to make sure we have an email/username/guid for them, or we need to throw an error.
      if(!isset($fields[4]) || !isset($fields[1]) || !isset($fields[2]))
      {
        return "user did not have necissary fields to allow for them to have a profile. Please check info for following user: " . print_r($fields);
      }
    }
    else 
    {
      $this_user = User::load($uid);
    }
    if( isset( $fields[4] )){ $this_user->set('init', $fields[4]); }
    if( isset( $fields[2] )){ $this_user->setUsername(strtolower($fields[2])); }
    if( isset( $fields[1] )){ $this_user->setPassword($fields[1]); }
    if( isset( $fields[4] )){ $this_user->setEmail($fields[4]); }
    //add in other custom user fields if they exist in the passed array
    if( isset( $fields[1] )){ $this_user->set('field_guid', $fields[1]); }
    if( isset( $fields[3] )){ $this_user->set('field_display_name', $fields[3]); }
    if( isset( $fields[5] )){ $this_user->set('field_given_name', $fields[5]); }
    if( isset( $fields[6] )){ $this_user->set('field_surname', $fields[6]); }
    if( isset( $fields[7] )){ $this_user->set('field_phone', $fields[7]); }
    if( isset( $fields[8] )){ $this_user->set('field_title', $fields[8]); }
    if( isset( $fields[9] )){ $this_user->set('field_department', $fields[9]); }
    if( isset( $fields[10] )){ $this_user->set('field_office', $fields[10]); }
    if( isset( $fields[11] )){ $this_user->set('field_organization_code', $fields[11]); }
    if( isset( $fields[12] )){ $this_user->set('field_company', $fields[12]); }
    if( isset( $fields[13] )){ $this_user->set('field_street', $fields[13]); }
    if( isset( $fields[14] )){ $this_user->set('field_city', $fields[14]); }
    if( isset( $fields[15] )){ $this_user->set('field_province', $fields[15]); }
    if( isset( $fields[16] )){ $this_user->set('field_postal_code', $fields[16]); }

    // We need this to for sure be a new user - we don't want to edit an existing user.
    if($type == 'add')
    {
      $this_user->enforceIsNew(true);
    }
    // This unpublishes their account if they are supposed to be deleted, or activates it if it is an update or add
    $type ==  'delete'?$this_user->block():$this_user->activate();
    // TODO: Validate this user once the Symphony error is fixed.
    //$violations_user = $this_user->validate();
    //if ($violations_user->count() > 0) 
   // {
    //  $violation = $violations_user[0]; 
     // \Drupal\Core\Messenger\MessengerInterface::addMessage($violation->getMessage(),'warning');
      //AtworkIdirLog::errorCollect($violation->getMessage()); 
    //}

    // Save user
    $result = $this_user->save();
    $return_value = "Code was not returned";
    if($result == 1)
    {
      $return_value = 'New user ' . $fields[2] . ' created';
    }
    if($result == 2)
    {
      $return_value = 'User ' . $fields[2] . ' Updated';
    }
    return $return_value;
  }
}