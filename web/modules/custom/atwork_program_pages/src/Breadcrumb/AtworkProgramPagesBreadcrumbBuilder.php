<?php

namespace Drupal\atwork_program_pages\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;


/**
 * Define class and implement BreadcrumbBuilderInterface.
 */
class AtworkProgramPagesBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   *
   * @return bool
   *   Must return a true or False
   */
  public function applies(RouteMatchInterface $attributes) {
    $path_found = FALSE;

    // Get all parameters.
    $parameters = $attributes->getParameters()->all();
    ksm($parameters);

    return $path_found;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    // Define a new object of type Breadcrumb.
    $breadcrumb = new Breadcrumb();

    // Return breadcrumb.
    return $breadcrumb;
  }

}
