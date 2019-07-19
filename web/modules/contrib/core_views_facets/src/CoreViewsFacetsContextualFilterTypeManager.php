<?php

namespace Drupal\core_views_facets;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\core_views_facets\Annotation\CoreViewsFacetsContextualFilterType;

/**
 * Provides the Core views facets filter type plugin manager.
 *
 * @see \Drupal\core_views_facets\Annotation\CoreViewsFacetsContextualFilterType
 * @see \Drupal\facets\Processor\ProcessorInterface
 * @see \Drupal\facets\Processor\ProcessorPluginBase
 * @see plugin_api
 */
class CoreViewsFacetsContextualFilterTypeManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets/processor/contextual_filter_type', $namespaces, $module_handler, CoreViewsFacetsFilterTypeInterface::class, CoreViewsFacetsContextualFilterType::class);

    $this->setCacheBackend($cache_backend, 'core_views_facets_core_views_facets_contextual_filter_type_plugins');
  }

}
