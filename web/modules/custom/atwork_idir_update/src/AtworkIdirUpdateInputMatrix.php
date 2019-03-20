<?php
/**
 * Created by PhpStorm.
 * User: TWERDAL
 * Date: 2019-03-20
 * Time: 2:08 PM
 */

namespace Drupal\atwork_idir_update;

class AtworkIdirUpdateInputMatrix {


  private $input_matrix;
  protected $config;


  function __construct()
  {
    $this->config = \Drupal::config('atwork_idir_update.atworkidirupdateadminsettings');
    $this->input_matrix = $this->atworkBuildMatrix();
  }


  public function getInputMatrix(){
    return $this->input_matrix;
  }
  /**
   * Helper function that creates an array that contains Drupal field, and the column number for the corresponding value if it exists in the tsv
   *
   */
  private function atworkBuildMatrix(){
    $columns = $this->getColumnNames();
    $user_fields = $this->getFillableFields();
    $input_matrix = array();
    foreach($user_fields as $key=>$value){
      if(in_array($this->config->get($key), $columns)){
        $input_matrix[$key] = array_search($this->config->get($key), $columns);
      }
    }
    // We also need to add in our Action field
    $input_matrix['action'] = array_search($this->config->get('action'), $columns);
    return $input_matrix;
  }

  /**
   * Helper method that gathers and returns the column labels in a csv field.
   * @return array|false|null
   */
  private function getColumnNames(){
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

  /**
   * Helper function to create an array of user fields that we ccan expose to the admin, so they can map .csv entries.
   * @return array $user_fields A collection of user fields after removeing the ones we shouldn't expose to the user.
   */
  private function getFillableFields(){
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
}
