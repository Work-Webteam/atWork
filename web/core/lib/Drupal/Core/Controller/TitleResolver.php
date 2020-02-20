<?php

namespace Drupal\Core\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides the default implementation of the title resolver interface.
 */
class TitleResolver implements TitleResolverInterface, CacheableTitleResolverInterface {
  use StringTranslationTrait;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The argument resolver.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface
   */
  protected $argumentResolver;

  /**
   * Constructs a TitleResolver instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface $argument_resolver
   *   The argument resolver.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, TranslationInterface $string_translation, ArgumentResolverInterface $argument_resolver) {
    $this->controllerResolver = $controller_resolver;
    $this->stringTranslation = $string_translation;
    $this->argumentResolver = $argument_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request, Route $route) {
    return $this->doGetTitle($request, $route);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableTitle(Request $request, Route $route) {
    $cacheable_title = new CacheableTitle();
    $title = $this->doGetTitle($request, $route, $cacheable_title);
    $cacheable_title->setTitle($title);
    return $cacheable_title;
  }

  /**
   * Returns a static or dynamic title for the route.
   *
   * This supports both cacheable and non-cacheable titles.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object passed to the title callback.
   * @param \Symfony\Component\Routing\Route $route
   *   The route information of the route to fetch the title.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   The cacheable metadata to attach the cacheable metadata of the title to.
   *
   * @return array|string|null
   *   The cacheable title for the route.
   *
   * @see \Drupal\Core\Controller\CacheableTitleResolverInterface::getCacheableTitle()
   * @see \Drupal\Core\Controller\TitleResolverInterface::getTitle()
   */
  protected function doGetTitle(Request $request, Route $route, CacheableMetadata &$cacheable_metadata = NULL) {
    $route_title = NULL;
    // A dynamic title takes priority. Route::getDefault() returns NULL if the
    // named default is not set.  By testing the value directly, we also avoid
    // trying to use empty values.
    if ($callback = $route->getDefault('_title_callback')) {
      $callable = $this->controllerResolver->getControllerFromDefinition($callback);
      if ($cacheable_metadata) {
        $request->attributes->set('cacheable_metadata', $cacheable_metadata);
      }
      $arguments = $this->argumentResolver->getArguments($request, $callable);
      $route_title = call_user_func_array($callable, $arguments);
    }
    elseif ($title = $route->getDefault('_title')) {
      $options = [];
      if ($context = $route->getDefault('_title_context')) {
        $options['context'] = $context;
      }
      $args = [];
      if (($raw_parameters = $request->attributes->get('_raw_variables'))) {
        foreach ($raw_parameters->all() as $key => $value) {
          $args['@' . $key] = $value;
          $args['%' . $key] = $value;
        }
        if ($cacheable_metadata) {
          $cacheable_metadata->addCacheContexts(['route']);
        }
      }
      if ($title_arguments = $route->getDefault('_title_arguments')) {
        $args = array_merge($args, (array) $title_arguments);
      }

      // Fall back to a static string from the route.
      $route_title = $this->t($title, $args, $options);
    }
    return $route_title;
  }

}
