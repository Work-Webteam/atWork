<?php

namespace Drupal\tether_stats;

use Symfony\Component\HttpFoundation\ParameterBag;
use Drupal\tether_stats\Entity\TetherStatsDerivative;

/**
 * A parameter collection which represents a Tether Stats identity set.
 *
 * An identity set is a collection of parameters that uniquely defines a
 * stat element. The only parameters which contribute to the identity set
 * can be found in getAllowableKeys(). All other parameter keys will be
 * ignored.
 *
 * An identity set must adhere one of the following states:
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
 */
class TetherStatsIdentitySet extends ParameterBag implements TetherStatsIdentitySetInterface {

  /**
   * Internal parameter to cache the validity state of this identity set.
   *
   * @var bool
   */
  private $isValidSet;

  /**
   * Set the validation to ignore the disabled derivative test.
   *
   * Setting this property will cause the isValid() method to ignore the
   * status of a derivative. This allows the use of disabled derivatives to
   * pass validation and is good for identifying elements which are no
   * longer in use.
   *
   * @var bool
   */
  private $ignoreDisabledDerivativeOnValidate;

  /**
   * {@inheritdoc}
   */
  public static function getAllowableKeys() {

    return [
      'name',
      'entity_type',
      'entity_id',
      'url',
      'query',
      'derivative',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function reduceToAllowableKeys(array $params) {

    $keys = array_flip(TetherStatsIdentitySet::getAllowableKeys());

    return array_intersect_key($params, $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityParams() {

    return TetherStatsIdentitySet::reduceToAllowableKeys($this->parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($reset = FALSE) {

    if (!isset($this->isValidSet) || $reset) {

      $this->isValidSet = $this->testValidity();
    }
    return $this->isValidSet;
  }

  /**
   * {@inheritdoc}
   */
  public function setIgnoreDisabledDerivativeOnValidate($value = TRUE) {

    $this->ignoreDisabledDerivativeOnValidate = $value;
  }

  /**
   * Internal method which tests the validity of this identity set.
   *
   * @see TetherStatsIdentitySet::isValid()
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
  private function testValidity() {

    $is_valid = FALSE;

    if ($this->has('name')) {

      if (!preg_match('@[^a-zA-Z0-9_\-]+@', $this->get('name'))) {

        // The entity_id and entity_type parameters must not be set if this is
        // a uniquely named element.
        if (!$this->has('entity_id') && !$this->has('entity_type')) {

          $is_valid = TRUE;
        }
        else {

          throw new \Drupal\tether_stats\Exception\TetherStatsIncompleteIdentitySetException();
        }
      }
      else {

        throw new \InvalidArgumentException(t('Invalid name provided. It must must contain only lowercase letters, numbers, hyphens and underscores.'));
      }
    }
    elseif ($this->has('entity_id') || $this->has('entity_type')) {

      if ($this->has('entity_id') && $this->has('entity_type')) {

        $entity_type = \Drupal::entityTypeManager()->getDefinition($this->get('entity_type'), FALSE);

        if ($entity_type && !empty($this->get('entity_id'))) {

          // We could load the entity to confirm it exists, but that would be
          // too costly, so instead we just confirm that the entity_type is
          // valid. As such, it is possible to create elements for entities
          // that don't exist so care should be taken when supplying an
          // entity_id.
          $is_valid = TRUE;
        }
        else {

          throw new \Drupal\tether_stats\Exception\TetherStatsEntityInvalidException();
        }
      }
      else {

        throw new \Drupal\tether_stats\Exception\TetherStatsIncompleteIdentitySetException();
      }
    }
    elseif ($this->has('url')) {

      $is_valid = TRUE;
    }
    else {

      throw new \Drupal\tether_stats\Exception\TetherStatsIncompleteIdentitySetException();
    }

    // Validate the derivative if one is specified.
    if ($is_valid && $this->has('derivative')) {

      $derivative = TetherStatsDerivative::load($this->get('derivative'));

      if (isset($derivative)) {

        if (!empty($this->ignoreDisabledDerivativeOnValidate) || $derivative->status()) {

          // Entity Derivatives may have restrictions on what entity types and
          // bundles they can apply to.
          if ($derivative->hasDerivativeEntityType()) {

            if ($this->has('entity_type') && $derivative->getDerivativeEntityType() == $this->get('entity_type')) {

              // If the derivative has a bundle specified, then we must load the
              // entity to confirm that the bundle matches.
              if ($derivative->hasDerivativeBundle()) {

                $entity = entity_load($derivative->getDerivativeEntityType(), $this->get('entity_id'));

                if (isset($entity)) {

                  if ($entity->bundle() != $derivative->getDerivativeBundle()) {

                    throw new \Drupal\tether_stats\Exception\TetherStatsDerivativeInvalidException();
                  }
                }
                else {

                  throw new \Drupal\tether_stats\Exception\TetherStatsEntityInvalidException();
                }
              }
            }
            else {

              throw new \Drupal\tether_stats\Exception\TetherStatsDerivativeInvalidException();
            }
          }
        }
        else {

          throw new \Drupal\tether_stats\Exception\TetherStatsDerivativeDisabledException();
        }
      }
      else {

        throw new \Drupal\tether_stats\Exception\TetherStatsDerivativeNotFoundException();
      }
    }

    return $is_valid;
  }

  /**
   * {@inheritdoc}
   */
  public function replace(array $parameters = []) {

    unset($this->isValidSet);
    parent::replace($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function add(array $parameters = []) {

    unset($this->isValidSet);
    parent::add($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function get($path, $default = NULL, $deep = FALSE) {

    return parent::get($path, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {

    unset($this->isValidSet);
    parent::set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {

    $type = NULL;

    if ($this->has('name')) {

      $type = TetherStatsIdentitySetInterface::CATEGORY_NAME;
    }
    elseif ($this->has('entity_type')) {

      $type = TetherStatsIdentitySetInterface::CATEGORY_ENTITY;
    }
    elseif ($this->has('url')) {

      $type = TetherStatsIdentitySetInterface::CATEGORY_URL;
    }
    return $type;
  }

}
