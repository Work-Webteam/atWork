<?php

namespace Drupal\tether_stats;

use Drupal\router_test\Access\DefinedTestAccessCheck;
/**
 * Interface for TetherStatsIdentitySet.
 */
interface TetherStatsIdentitySetInterface {

  /**
   * The uniquely named identity set category.
   *
   * Identity sets can be divided into three distinct types. When identity sets
   * have a unique "name" key, this type is applicable.
   *
   * @var int
   */
  const CATEGORY_NAME = 0b00000001;

  /**
   * The entity bound identity set category.
   *
   * Identity sets can be divided into three distinct types. When identity sets
   * have an entity_type and entity_id, binding them to an entity, then this
   * type is applicable.
   *
   * @var int
   */
  const CATEGORY_ENTITY = 0b00000010;

  /**
   * The url based identity set category.
   *
   * Identity sets can be divided into three distinct types. When identity sets
   * have a url and are not any of the other types, then this type is
   * applicable. In this category, the url and query parameters together
   * uniquely define an element. The query parameter is optional.
   *
   * The CATEGORY_URL type is considered a loose type and will only apply if it
   * is not a CATEGORY_NAME or a CATEGORY_ENTITY.
   *
   * @var int
   */
  const CATEGORY_URL = 0b00000100;

  /**
   * Returns a parameter by name.
   *
   * @param string $path
   *   The parameter key.
   * @param mixed $default
   *   The default value if the parameter key does not exist.
   * @param bool $deep
   *   Deep parameters are not supported. This parameter is
   *   ignored.
   *
   * @return mixed
   *   The parameter value.
   */
  public function get($path, $default = NULL, $deep = FALSE);

  /**
   * Gets the array of allowable parameter keys.
   *
   * The allowable keys are the following:
   *   - name: The unique id string for individual elements.
   *   - entity_type: The entity type for entity bound elements.
   *   - entity_id: The entity id for entity bound elements.
   *   - url: The url of the element if it is a page.
   *   - query: The query string of the element, if applicable.
   *   - derivative: The derivative id for derived entities.
   *
   * @return array
   *   The array of allowable parameter keys.
   */
  public static function getAllowableKeys();

  /**
   * Reduces the array to only keys allowable for identity sets.
   *
   * @param array $params
   *   The associative array of parameters to reduce.
   *
   * @return array
   *   The reduced array.
   */
  public static function reduceToAllowableKeys(array $params);

  /**
   * Gets an array of all parameters that have allowable keys.
   *
   * @return array
   *   The associative array of identity set key => value pairs.
   */
  public function getIdentityParams();

  /**
   * Determines if this identity set is valid.
   *
   * An identity set is valid if one of the following states apply:
   *
   * A - The set identifies an element by a unique machine name.
   *
   * At least the "name" parameter is specified and must must contain only
   * lowercase letters, numbers, hyphens and underscores.
   *
   * B - An element is associated with an entity.
   *
   * At least the "entity_id" and "entity_type" parameters are specified and
   * these correspond to a valid Drupal entity.
   *
   * C - An element is associated to a URL.
   *
   * At least the "url" parameter is provided. The "query" parameter may
   * also be set, but it will only contribute to uniqueness if query
   * strings are allowed to identify new elements. For this state, none of
   * the "name", "entity_type" and "entity_id" parameters can be set.
   *
   *
   * A derivative may be applied to any of  the above states by setting the
   * "derivative" parameter. This string must uniquely identify a
   * TetherStatsDerivative. If the identity set refers to an entity, state B,
   * derivatives may apply only to specific entity types or bundles depending
   * on the derivative settings.
   *
   * Uniqueness is determined with states taking precedence from the top down.
   * That is, when a "name" parameter is provided, this set will be regarded
   * as type A and having additional "entity_id" or "entity_type" parameters
   * would invalidate the set.
   *
   * @param bool $reset
   *   Reset the cached value and force another validity test.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a name is set but it is not a valid machine name.
   * @throws \Drupal\tether_stats\Exception\TetherStatsInvalidEntityException
   *   Thrown when an invalid entity_type or entity_id combination is
   *   provided.
   * @throws \Drupal\tether_stats\Exception\TetherStatsIncompleteIdentitySetException
   *   Thrown when there are insufficient parameters set to complete the
   *   identity set for one of the states A, B, and C above.
   * @throws \Drupal\tether_stats\Exception\TetherStatsEntityInvalidException
   *   Thrown when entity_type and entity_id parameters are provided but the
   *   entity is invalid.
   * @throws \Drupal\tether_stats\Exception\TetherStatsDerivativeNotFoundException
   *   Thrown when a "dervative" parameter was provided that but no derivative
   *   by that name exists.
   * @throws \Drupal\tether_stats\Exception\TetherStatsDerivativeDisabledException
   *   Thrown when a "dervative" parameter was provided but that derivaitive is
   *   not enabled for use.
   * @throws \Drupal\tether_stats\Exception\TetherStatsDerivativeInvalidException
   *   Thrown when the derivative parameter provided can not be applied to
   *   to the entity given by entity_id and entity_type.
   *
   * @return bool
   *   Returns TRUE if this identity set is valid and will uniquely identify
   *   a stats element.
   */
  public function isValid($reset = FALSE);

  /**
   * Set the validation to ignore the disabled derivative test.
   *
   * Setting this to TRUE will cause the isValid() method to ignore the
   * status of a derivative. This allows the use of disabled derivatives to
   * pass validation and is good for identifying elements which are no
   * longer in use.
   *
   * @param bool $value
   *   The value to set.
   */
  public function setIgnoreDisabledDerivativeOnValidate($value = TRUE);

  /**
   * Determines the category this identity set falls in.
   *
   * This method is useful for testing purposes. It does not, however, validate
   * the set and may return null or unexpected results if invalid.
   *
   * @return int|null
   *   Returns one of CATEGORY_NAME, CATEGORY_ENTITY, or CATEGORY_URL constants
   *   as defined in TetherStatsIdentitySetInterface. If the category cannot be
   *   determined, returns null.
   */
  public function getType();

}
