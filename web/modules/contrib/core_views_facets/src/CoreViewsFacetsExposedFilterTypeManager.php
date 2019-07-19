<?php

namespace Drupal\core_views_facets;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\core_views_facets\Annotation\CoreViewsFacetsExposedFilterType;

/**
 * Provides the Core views facets filter type plugin manager.
 *
 * @see \Drupal\core_views_facets\Annotation\CoreViewsFacetsExposedFilterType
 * @see \Drupal\facets\Processor\ProcessorInterface
 * @see \Drupal\facets\Processor\ProcessorPluginBase
 * @see plugin_api
 */
class CoreViewsFacetsExposedFilterTypeManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets/processor/exposed_filter_type', $namespaces, $module_handler, CoreViewsFacetsFilterTypeInterface::class, CoreViewsFacetsExposedFilterType::class);

    $this->setCacheBackend($cache_backend, 'core_views_facets_core_views_facets_exposed_filter_type_plugins');
  }

}
