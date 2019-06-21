<?php

namespace Drupal\draggable_dashboard\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Dashboard entity.
 *
 * @ConfigEntityType(
 *   id = "dashboard_entity",
 *   label = @Translation("Dashboard"),
 *   config_prefix = "dashboard_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "description",
 *     "columns",
 *     "blocks"
 *   }
 * )
 */
class DashboardEntity extends ConfigEntityBase implements DashboardEntityInterface {

  /**
   * The Dashboard ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Dashboard title.
   *
   * @var string
   */
  protected $title;

  /**
   * The Dashboard description.
   *
   * @var string
   */
  protected $description;

  /**
   * The Dashboard columns count.
   *
   * @var string
   */
  protected $columns;

  /**
   * The Dashboard blocks.
   *
   * @var string
   */
  protected $blocks;

}
