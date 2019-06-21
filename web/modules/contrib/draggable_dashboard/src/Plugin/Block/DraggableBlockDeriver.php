<?php

namespace Drupal\draggable_dashboard\Plugin\Block;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\draggable_dashboard\Entity\DashboardEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This deriver creates a block for every dashboard that has been created.
 */
class DraggableBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $base_plugin_id = $base_plugin_definition['id'];

    if (!isset($this->derivatives[$base_plugin_id])) {

      $plugin_derivatives = [];

      $dashboards = \Drupal::entityQuery('dashboard_entity')->execute();;

      foreach ( $dashboards as $dashboardID ){
        $dashboard = DashboardEntity::load($dashboardID);
        $machine_name = 'draggable_dashboard_' . $dashboardID;
        $plugin_derivatives[$machine_name] = [
            'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $machine_name,
            'label' => t('Dashboard: :dashboard', [':dashboard' => $dashboard->get('title')]),
            'admin_label' => $dashboard->get('title'),
            'description' => $dashboard->get('description'),
          ] + $base_plugin_definition;
      }
      $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    }
    return $this->derivatives[$base_plugin_id];

  }

}
