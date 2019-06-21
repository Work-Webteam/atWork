<?php

namespace Drupal\postal_code;


/**
 * Provide additional methods for validations of postal code field.
 */
class PostalCodeValidation implements PostalCodeValidationInterface {

  /**
   * An array of country code => validation pattern.
   */
  protected $countries;

  /**
   * {@inheritdoc}
   */
  public function getValidationPatterns() {
    // Populate the country list if it is not already populated.
    if (!isset($this->countries)) {
      $this->countries = static::getAllowedValidationPatterns();
    }

    return $this->countries;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($country_code, $value) {
    $postal_code_validation_data = $this->getValidationPatterns();
    $regex = $postal_code_validation_data[$country_code][0];
    $error_array[] = preg_match($regex, $value);
    return $error_array;
  }

  /**
   * Get an array of all two-letter country code => country name pairs.
   *
   * @return array
   *   An array of country code => country name pairs.
   */
  static public function getAllowedValidationPatterns() {
    return array(
      'us' => array('/^\d{5}(-\d{4})?$/'),
      'ca' => array('/^[ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ] ?\d[ABCEGHJKLMNPRSTVWXYZ]\d$/'),
      'gb' => array('/^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$/i'),
      'de' => array('/^\b((?:0[1-46-9]\d{3})|(?:[1-357-9]\d{4})|(?:[4][0-24-9]\d{3})|(?:[6][013-9]\d{3}))\b$/'),
      'fr' => array('/^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$/'),
      'it' => array('/^(V-|I-)?[0-9]{5}$/'),
      'au' => array('/^(0[289][0-9]{2})|([1345689][0-9]{3})|(2[0-8][0-9]{2})|(290[0-9])|(291[0-4])|(7[0-4][0-9]{2})|(7[8-9][0-9]{2})$/'),
      'nl' => array('/^[1-9][0-9]{3}\s?([a-zA-Z]{2})?$/'),
      'es' => array('/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/'),
      'dk' => array('/^([D-d][K-k])?( |-)?([0-9]{1})?[0-9]{3}$/'),
      'se' => array('/^(s-|S-){0,1}[0-9]{3}\s?[0-9]{2}$/'),
      'be' => array('/^[1-9]{1}[0-9]{3}$/'),
      'in' => array('/^([1-9][0-9]{2}\s?[0-9]{3})$/'),
      'pl' => array('/^[0-9]{2}-[0-9]{3}$/'),
    );
  }

}
