<?php

namespace Drupal\tether_stats;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages chart renderer plugins.
 *
 * Chart renderer plugins are responsible for drawing charts from
 * analytics data.
 *
 * @see \Drupal\tether_stats\Annotation\TetherStatsChartRenderer
 * @see \Drupal\tether_stats\TetherStatsChartRendererInterface
 * @see plugin_api
 */
class TetherStatsChartRendererPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new ImageEffectManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/tether_stats/Chart', $namespaces, $module_handler, 'Drupal\tether_stats\TetherStatsChartRendererInterface', 'Drupal\tether_stats\Annotation\TetherStatsChartRenderer');

    $this->setCacheBackend($cache_backend, 'tether_stats_chart_renderer_plugins');
  }

}
