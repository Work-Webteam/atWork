<?php

namespace Drupal\postal_code;


/**
 * Interface ValidatorServiceInterface.
 * Provide interface with additional methods for validations of postal code field.
 */
interface PostalCodeValidationInterface {

  /**
   * Actual validation function.
   *
   * @return array of RegExp patterns for validation.
   */
  public function getValidationPatterns();

  /**
   * Custom function defining regexes corresponding to different countries.
   *
   * @param string $country_code
   *   Short country code.
   * @param string $value
   *   Value to be validated.
   *
   * @return array
   *   Array of errors.
   */
  public function validate($country_code, $value);

}
