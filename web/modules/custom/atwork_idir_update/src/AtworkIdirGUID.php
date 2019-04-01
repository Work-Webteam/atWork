<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * This class is the parent of the parse classes. We deal with updating users and checking users here, but this class won't be invoked on its own.
 */
class AtworkIdirGUID
{
  protected $timestamp;
  protected $drupal_path;
  // This is a user object that can be set with an array of user info, generally used by child classes and passed back to the updateSystemUser() method
  protected $new_fields = [];
  protected $input_matrix;
  
  function __construct()
  {
    // Use timestamp and drupal_path mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    // grab the path to the Public:// file folder
    $this->drupal_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/';
    // Get our most current matrix
    $this->input_matrix = $this->setInputMatrix();
  }

  protected function getModulePath($moduleName)
  {
    return drupal_get_path('module', $moduleName);
  }

  protected function setInputMatrix(){
    $current_matrix = new AtworkIdirUpdateInputMatrix();
    return $current_matrix->getInputMatrix();
  }

  protected function getInputMatrix() {
    return $this->input_matrix;
  }

    /** 
   * This function takes a GUID input, then returns user number (if the user is in our system) or else false if not in our system
   */
  public function getGUIDField($guid)
  {

    $connection = \Drupal::database();
    $result = $connection->select('user__field_user_guid', 'fg')
      ->fields('fg', array('entity_id'))
      ->distinct(true)
      ->condition("fg.field_user_guid_value", $guid, '=')
      ->execute()->fetchCol();

      return $result;
  }

   /**
   * updateSystemUser: This function makes any necessary updates to our db for the user. By the time the user gets here, we know we need to make some changes. Shared by add/delete and update methods. Once successful, send a note to the success method and return
   *
   * @param [string] $type : This denotes if this is an update or a delete
   * @param  [string] $uid : userid we found when checking for a user - can load with this
   * @param [array] $fields : An array of fields we need for the user.  
   * @return string
   */
  public function updateSystemUser($type, $uid, $fields)
  {
    $this_user = null;
    // User fields are updated with new info, and user is saved.
    if( $type == 'add' ){
      $this_user = User::create();
      // If this is a new user - we have to make sure we have an email/username/guid for them, or we need to throw an error.
      if(!isset($this->input_matrix['name']) || !isset($this->input_matrix['field_user_guid']))
      {
        return "user did not have necessary fields (username or guid) to allow for them to have a profile. Please check info for following user: " . print_r($fields);
      }
      // We need to set a user password on initial import - something hashed.
      // We need to create a hashed password for this user in case we don't set one from a field. We will use the GUID seeing as we know we need this - it will be hashed on save.
      $this_user->setPassword($fields[$this->input_matrix["field_user_guid"]]);
    }
    else 
    {
      // This is not a brand new user - and we need to update fields rather than add a new user object.
      $this_user = User::load($uid);
    }
    // Need to take specific special case fields into account (have their own setters)
    $special_cases = [
      'init'=>'init',
      'name'=>'name',
      'pass'=>'pass',
      'mail'=>'mail'
    ];
    // grab all user fields from AtowrkIdirUpdateInputMatrix - returns an array
    $matrix = new AtworkIdirUpdateInputMatrix();
    $fillable_user_fields = $matrix->getUserFieldArray();
    $all_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');

    // Loop through all available user fields
    foreach($fillable_user_fields as $key=>$value){
      // If our field is included in the matrix....
      if(array_key_exists($key, $this->input_matrix)){
        // Check if it is a special case
        if(in_array($key, $special_cases)){
          // Debatable whether we require a switch here or should stick just with if's - but it definitely makes it easier to read.
          switch ($key){
            case "init":
              if(isset($fields[$this->input_matrix["init"]])){
                $this_user->set('init', $fields[$this->input_matrix["init"]]);
              }
              break;
            case "name":
              if(isset($fields[$this->input_matrix["name"]])){
                $this_user->setUsername(strtolower($fields[$this->input_matrix["name"]]));
              }
              break;
            case "pass":
              if(isset($fields[$this->input_matrix["pass"]])){
                $this_user->setPassword($fields[$this->input_matrix["pass"]]);
              }
              break;
            case "mail":
              if(isset($fields[$this->input_matrix["mail"]])){
                $this_user->setEmail($fields[$this->input_matrix["mail"]]);
              }
          }
        } else {
          // Set it with appropriate column value.
          // This is kind of Hacky - but the drupal default validators are difficult to manage (especially in a cron run).
          // Want to make sure our postal-code fits in the field - some users have two for some reason.
          $field_type = $this_user->get($key)->getFieldDefinition()->getType();
          if($field_type == "postal_code"){
            isset($this->input_matrix[$key]) ? $this_user->set($key, substr($fields[$this->input_matrix[$key]], 0, 7)) : $this_user->set($key, "");

          } else {
            isset($this->input_matrix[$key]) ? $this_user->set($key, $fields[$this->input_matrix[$key]]) : $this_user->set($key, "");
          }
        }
      }
    }
    /* Not used anymore - but helpful to show what the original mapping was (some fields changed names slightly)
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
    */
    // We need this to for sure be a new user - we don't want to edit an existing user.
    if($type == 'add')
    {
      $this_user->enforceIsNew(true);
    }
    // This unpublishes their account if they are supposed to be deleted, or activates it if it is an update or add
    $type ==  'delete'?$this_user->block():$this_user->activate();
    // TODO: Validate this user once the Symphony error is fixed.
    /*
    $violations_user = $this_user->validate();
    if ($violations_user->count() > 0)
    {
      $violation = $violations_user[0];
      \Drupal\Core\Messenger\MessengerInterface::addMessage($violation->getMessage(),'warning');
      AtworkIdirLog::errorCollect($violation->getMessage());
    }
    */
    // Save user
    $result = $this_user->save();
    $return_value = "The system did not record and update or create user " . $this_user->field_user_display_name->value;
    if($result == 1)
    {
      $return_value = 'New user ' . $this_user->field_user_display_name->value . ' created';
    }
    if($result == 2)
    {
      $return_value = 'User ' . $this_user->field_user_display_name->value . ' Updated';
    }
    return $return_value;
  }
}
