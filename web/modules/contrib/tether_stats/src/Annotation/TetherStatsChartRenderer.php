<?php

namespace Drupal\tether_stats\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a chart renderer annotation object.
 *
 * Plugin Namespace: Plugin\tether_stats\Chart.
 *
 * @Annotation
 *
 * @see \Drupal\tether_stats\TetherStatsChartRendererPluginManager
 * @see \Drupal\tether_stats\TetherStatsChartRendererInterface
 * @see plugin_api
 */
class TetherStatsChartRenderer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the chart renderer tool.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
