<?php

namespace Drupal\tether_stats;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a derivative entity.
 */
interface TetherStatsDerivativeInterface extends ConfigEntityInterface {

  /**
   * Gets the Tether Stats manager service.
   *
   * @return TetherStatsManagerInterface
   *   The manager service.
   */
  public function getTetherStatsManager();

  /**
   * Gets the description text for the derivative.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

  /**
   * Gets the entity_type to which this derivative applies.
   *
   * @return string|null
   *   The entity_type this derivative applies to or null if no entity type
   *   applies.
   */
  public function getDerivativeEntityType();

  /**
   * Gets the bundle to which this derivative applies.
   *
   * If NULL, then this derivative applies to all bundles of
   * the applicable entity_type.
   *
   * @return string|null
   *   The bundle this derivative applies to or NULL if no
   *   bundle applies.
   */
  public function getDerivativeBundle();

  /**
   * Determines if the derivative has an entity type constraint.
   *
   * @return bool
   *   TRUE if the derivative is constrained to an entity type.
   */
  public function hasDerivativeEntityType();

  /**
   * Determines if the derivative has a bundle constraint.
   *
   * This only applies if there is an entity type constraint.
   *
   * @return bool
   *   TRUE if the derivative is constrained to a bundle type.
   */
  public function hasDerivativeBundle();

  /**
   * Gets the scope to which this derivative applies to.
   *
   * Constructs a string of the form "{entity_type}:{bundle}" which
   * describes the derivative's scope. For example, in the case
   * of "node:article" this derivative will apply only to stat
   * elements for article pages.
   *
   * The bundle may be NULL, in which case "{entity_type}:*" will
   * be returned where '*' implies that the derivative applies to
   * all bundles. For example, "node:*" applies to all node pages
   * regardless of the node type. Similarly, "user:*" will apply
   * to stat elements corresponding to users.
   *
   * @return string
   *   The entity and bundle this derivative applies as one string
   *   of the form {entity_type}:{bundle} where {bundle} will be *
   *   if not set.
   */
  public function getDerivativeScope();

  /**
   * Retrieves a count of how many stat elements are from this derivative.
   *
   * @return int
   *   The usage count.
   */
  public function getUsageCount();

}
