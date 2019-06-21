<?php

namespace Drupal\draggable_dashboard\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Dashboard entities.
 */
interface DashboardEntityInterface extends ConfigEntityInterface {

  /**
   * Region name for draggable dashboard blocks.
   *
   * @var string
   */
  const BASE_REGION_NAME = 'draggable_dashboard_region';

  // Add get/set methods for your configuration properties here.
}
