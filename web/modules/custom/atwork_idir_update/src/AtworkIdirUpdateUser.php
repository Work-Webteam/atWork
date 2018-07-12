<?php
namespace Drupal\atwork_idir_update;
//use Drupal\atwork_idir_update\AtworkIdirUpdateUserInterface;
use Drupal\Database\Core\Database\Database;
use Drupal\Database\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\user\Entity\User;


class AtworkIdirUpdateUser implements iAtworkIdirUpdateUser  {
  public $user;

  /**
   * Construct a user object to use in the script
   *
   * @param [array] $user_array  if from .tsv
   * @param [string] $user_type : marker to let us know if this is a .tsv user ("tsv") or a system user object that needs to be created (system) 
   */
  function __construct( $user_type, $user_array ){
    if($user_type == "tsv"){
      $this->user = $user_array;
    } else {
      $this->user = $this->systemUser($user_array);
    }
  }

  /**
   * systemUser
   *
   * @param [array] $user_array : the user_array check on contains the array of the current tsv user we want to pull from the db.
   * @return [object] $system_user : We will pull this object if the user exists, and send it back
   */
  public function systemUser($user_array) 
  {
    // We need to check $guid and $username together (idir/drupal uname and guid as primary) to get the proper user from the system
     //$possible_user = \Drupal::entityQuery('user')
     // ->condition('uid', '2')
      //->condition('field_guid', $user_array['guid'])
     // ->execute();

    // Create an object of type SelectQuery
    //$query = db_select('users', 'u');
    
    // Add extra detail to this query object: a condition, fields and a range
    //$query->condition('u.uid', 0, '<>')
     // ->fields('u', array('uid'))
     // ->range(0, 50);

    //$possible_user = $query->execute();

    print_r(\Drupal::database()->query("show tables")->fetchALL());
      print_r($possible_user);
      //$system_user = User::load($possible_user);

    //return $system_user;
  }

  public function tsvUser($new_user){

  }
}