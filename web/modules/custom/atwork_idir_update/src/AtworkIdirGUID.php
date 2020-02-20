<?php

namespace Drupal\atwork_idir_update;

use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Class AtworkIdirGUID.
 *
 * This class is the parent of the parse classes.
 * We deal with updating users and checking users here,
 * but this class won't be invoked on its own.
 */
class AtworkIdirGUID {
  protected $timestamp;
  protected $drupalPath;
  /**
   * This array acts like an object.
   *
   * @var array
   * This is a user object that can be set with an array of user info,
   * generally used by child classes and
   * passed back to the updateSystemUser() method.
   */
  protected $newFields = [];
  protected $inputMatrix;

  /**
   * AtworkIdirGUID constructor.
   */
  public function __construct() {
    // Use timestamp and drupal_path
    // mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    // Grab the path to the Public:// file folder.
    $this->drupalPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/';
    // Get our most current matrix.
    $this->inputMatrix = $this->setInputMatrix();
  }

  /**
   * Function returns this module path.
   *
   * @param string $moduleName
   *   The module we are looking for.
   *
   * @return string
   *   The path to the module in this install
   */
  protected function getModulePath($moduleName) {
    return drupal_get_path('module', $moduleName);
  }

  /**
   * Setter for a new input array from settings.
   *
   * @return array
   *   The matrix of mapped fields as set in configs.
   */
  protected function setInputMatrix() {
    $current_matrix = new AtworkIdirUpdateInputMatrix();
    return $current_matrix->getInputMatrix();
  }

  /**
   * Getter for current input array.
   *
   * @return array
   *   Returns the current mapped fields fro config.
   */
  protected function getInputMatrix() {
    return $this->inputMatrix;
  }

  /**
   * Takes a GUID input, returns user number if available.
   *
   * @param string $guid
   *   The GUID we are looking for.
   *
   * @return mixed
   *   Return user id number, or NULL if not found.
   */
  public function getGUIDField($guid) {
    $connection = \Drupal::database();
    $result = $connection->select('user__field_user_guid', 'fg')
      ->fields('fg', array('entity_id'))
      ->distinct(TRUE)
      ->condition("fg.field_user_guid_value", $guid, '=')
      ->execute()->fetchCol();

    return $result;
  }

  /**
   * Attempts to update user if they exist.
   *
   * @param string $type
   *   Denotes if this is an update or a delete.
   * @param string $uid
   *   User id we found when checking for a user.
   * @param array $fields
   *   An array of fields we need for the user.
   *
   * @return string
   *   Returns string if successful or not for log.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Ends application and logs error.
   */
  public function updateSystemUser($type, $uid, array $fields) {
    $this_user = NULL;
    // User fields are updated with new info, and user is saved.
    if ($type == 'add') {
      $this_user = User::create();
      // If this is a new user -
      // we have to make sure we have an
      // email/username/guid for them, or we need to throw an error.
      if (!isset($this->inputMatrix['name']) || !isset($this->inputMatrix['field_user_guid'])) {
        return "user did not have necessary fields (username or guid) to allow for them to have a profile. Please check info for following user: " . print_r($fields);
      }
      // We need to set a user password on initial import - something hashed.
      // We need to create a hashed password for this user in case we don't
      // set one from a field. We will use the GUID seeing as we know
      // we need this - it will be hashed on save.
      $this_user->setPassword($fields[$this->inputMatrix["field_user_guid"]]);
    }
    else {
      // This is not a brand new user -
      // and we need to update fields rather than add a new user object.
      $this_user = User::load($uid);
    }
    // Need to take specific special case fields
    // into account (have their own setters).
    $special_cases = [
      'init' => 'init',
      'name' => 'name',
      'pass' => 'pass',
      'mail' => 'mail',
      'field_user_guid' => 'field_user_guid',
    ];
    // Grab all user fields from AtworkIdirUpdateInputMatrix as an array.
    $matrix = new AtworkIdirUpdateInputMatrix();
    $fillable_user_fields = $matrix->getUserFieldArray();

    // Loop through all available user fields.
    foreach ($fillable_user_fields as $key => $value) {
      // If our field is included in the matrix....
      if (array_key_exists($key, $this->inputMatrix)) {
        // Check if it is a special case.
        if (in_array($key, $special_cases)) {
          // Debatable whether we require a switch here or should stick
          // just with if's - but it definitely makes it easier to read.
          switch ($key) {
            case "init":
              if (isset($fields[$this->inputMatrix["init"]])) {
                $this_user->set('init', $fields[$this->inputMatrix["init"]]);
              }
              break;

            case "name":
              if (isset($fields[$this->inputMatrix["name"]])) {
                $this_user->setUsername(strtolower($fields[$this->inputMatrix["name"]]));
              }
              break;

            case "pass":
              if (isset($fields[$this->inputMatrix["pass"]])) {
                $this_user->setPassword($fields[$this->inputMatrix["pass"]]);
              }
              break;

            case "mail":
              if (isset($fields[$this->inputMatrix["mail"]])) {
                $this_user->setEmail($fields[$this->inputMatrix["mail"]]);
              }
              break;

            case "field_user_guid":
              // Don't want to mess with guid if this is a delete -
              // need to only have one instance in the system.
              $type == "delete"?:$this_user->set('field_user_guid', $fields[$this->inputMatrix["field_user_guid"]]);
              break;
          }
        }
        else {
          // Set it with appropriate column value.
          // This is kind of Hacky - but the drupal default
          // validators are difficult to manage (especially in a cron run).
          // Want to make sure our postal-code fits in the field -
          // some users have two for some reason.
          $field_type = $this_user
            ->get($key)
            ->getFieldDefinition()
            ->getType();
          if ($field_type == "postal_code") {
            isset($this->inputMatrix[$key]) ? $this_user->set($key, substr($fields[$this->inputMatrix[$key]], 0, 7)) : $this_user->set($key, "");
          } else {
            $this_user->set($key, $fields[$this->inputMatrix[$key]]);
          }
        }
      }
    }

    // We need this to for sure be a new user -
    // we don't want to edit an existing user.
    if ($type == 'add') {
      $this_user->enforceIsNew(TRUE);
    }
    // This unpublishes their account
    // if they are supposed to be deleted,
    // or activates it if it is an update or add.
    $type == 'delete' ? $this_user->block() : $this_user->activate();
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
    // Save user.
    $result = $this_user->save();
    $return_value = "The system did not record and update or create user " . $this_user->field_user_display_name->value;
    if ($result == 1) {
      $return_value = 'New user ' . $this_user->field_user_display_name->value . ' created';
    }
    if ($result == 2) {
      $return_value = 'User ' . $this_user->field_user_display_name->value . ' Updated';
    }
    return $return_value;
  }

}
