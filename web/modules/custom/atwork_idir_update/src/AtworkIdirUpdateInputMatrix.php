<?php

namespace Drupal\atwork_idir_update;

use Drupal\atwork_idir_update\Controller\AtworkIdirUpdateController;

/**
 * Class AtworkIdirUpdateInputMatrix.
 *
 * Collects settings configs and places them in an array to help
 * sort the .csv into the proper fields.
 *
 * @package Drupal\atwork_idir_update
 */
class AtworkIdirUpdateInputMatrix {

  private $inputMatrix;
  private $userFields;
  protected $config;

  /**
   * AtworkIdirUpdateInputMatrix constructor.
   */
  public function __construct() {
    $this->config = \Drupal::config('atwork_idir_update.atworkidirupdateadminsettings');
    $this->inputMatrix = $this->atworkBuildMatrix();
    $this->userFields = $this->setUserFieldsArray();
  }

  /**
   * Getter for matrix.
   *
   * @return array
   *   Array of user fields and .csv columns.
   */
  public function getInputMatrix() {
    return $this->inputMatrix;
  }

  /**
   * Setter for user field array from settings config.
   *
   * @return array
   *   Array of user fields mapping from settings.
   */
  private function setUserFieldsArray() {
    return $this->getFillableFields();
  }

  /**
   * Getter for current user fields.
   *
   * @return array
   *   Array of current available user fields.
   */
  public function getUserFieldArray() {
    return $this->userFields;
  }

  /**
   * Helper function that creates an array.
   *
   * Array that contains Drupal field,
   * and the column number for the corresponding value if it exists in the tsv.
   *
   * @return array
   *   Array matrix of Drupal and .tsv fields.
   */
  private function atworkBuildMatrix() {
    $columns = $this->getColumnNames();
    $user_fields = $this->getFillableFields();
    $input_matrix = array();
    foreach ($user_fields as $key => $value) {
      if (in_array($this->config->get($key), $columns)) {
        $input_matrix[$key] = array_search($this->config->get($key), $columns);
      }
    }
    // We also need to add in our Action field.
    $input_matrix['action'] = array_search($this->config->get('action'), $columns);
    return $input_matrix;
  }

  /**
   * Helper method that gathers and returns the column labels in a csv field.
   *
   * @return array|false|null
   *   Return array on success of getting column names.
   *
   * @throws \exception
   *   Stops application and logs error if we can't get .tsv.
   */
  private function getColumnNames() {
    $csv = [];
    // If we have a current .tsv, we can use that.
    $timestamp = date('Ymd');
    $exists = file_exists('public://idir/' . $timestamp . "/idir_" . $timestamp . ".tsv");
    if ($exists) {
      // We have a file, grab the first row and return it.
      $handle = fopen('public://idir/' . $timestamp . "/idir_" . $timestamp . ".tsv", "r");
      $csv = fgetcsv($handle, '', "\t");
      fclose($handle);
    }
    else {
      // Else we need to fire the controller
      // so we can pull one down, and then we can check again.
      $new_file = new AtworkIdirUpdateController();
      $generate_csv = $new_file->atworkIdirInit();
      \Drupal::logger('atwork_idir_update')->error($generate_csv);

      if (file_exists('public://idir/' . $timestamp . "/idir_" . $timestamp . ".tsv")) {
        // Now grab and add it - or throw an error and end.
        $handle = fopen('public://idir/' . $timestamp . "/idir_" . $timestamp . ".tsv", "r");
        $csv = fgetcsv($handle, '', "\t");
        fclose($handle);
      }
      else {
        // Throw an error.
        \Drupal::logger('atwork_idir_update')->error('Cannot access or download idir.csv file from location. Please check URL/User and Password and try again.');
        drupal_set_message("Cannot access or download idir.csv, no fields to generate. Please check credentials and try again.");
      }
    }

    return $csv;
  }

  /**
   * Helper function to create an array of user fields.
   *
   * We can expose user fields to the admin,
   * so they can map corresponding .tsv entries.
   *
   * @return mixed
   *   A collection of user fields after removing
   *   the ones we shouldn't expose to the user.
   */
  private function getFillableFields() {
    // Grab all usable user fields.
    $fields = \Drupal::service('entity_field.manager')->getFieldMap('user');
    $user_fields = $fields['user'];
    $fields = NULL;
    // We want to weed out the fields we definitely don't want to mess with.
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
      'message_digest',
    ];
    foreach ($default_fields as $key) {
      if (array_key_exists($key, $user_fields)) {
        unset($user_fields[$key]);
      }
    }
    return($user_fields);
  }

}
