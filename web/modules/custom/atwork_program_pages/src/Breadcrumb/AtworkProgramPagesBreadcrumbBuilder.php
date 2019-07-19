<?php

namespace Drupal\atwork_program_pages\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;

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
    // Determine if the current page is a program_area_content node.
    $is_node = isset($parameters['node']);
    $node_params_set = !empty($parameters['node']);
    $node_type = ($is_node && $node_params_set ? $parameters['node']->get('type')->getValue()[0]['target_id'] : FALSE);
    if ($node_type == "program_area_content") {
      $path_found = TRUE;
    }
    return $path_found;
  }

  /**
   * {@inheritdoc}
   *
   * Build out a breadcrumb map that takes parent program entity into account.
   */
  public function build(RouteMatchInterface $route_match) {
    $route_param = NULL;
    $parent_id = NULL;
    // Define a new object of type Breadcrumb.
    $breadcrumb = new Breadcrumb();
    // Build out the breadcrumb
    // Add a link to the homepage as our first crumb.
    $breadcrumb->addLink(Link ::createFromRoute('Home', '<front>'));
    // Get the route parameter for the current page.
    if (\Drupal::routeMatch()->getParameter('node')) {
      $route_param = \Drupal::routeMatch()->getParameter('node');
    }
    if ($route_param != NULL) {
      // Grab the parent id if it exists.
      $parent_id_value = $route_param->hasField('field_parent_program') ? $route_param->get('field_parent_program')->getValue() : NULL;
      $parent_id = $parent_id_value != NULL ? current(current($parent_id_value)) : NULL;
      // If we have an id, load the node.
      if ($parent_id != NULL) {
        $this_node = Node::load($parent_id);
        // Now grab the name/title of the parent.
        $parent_name = isset($this_node->get('title')->getValue()[0]['value']) ? $this_node->get('title')->getValue()[0]['value'] : NULL;
        // Get the URL of the parent for the breadcrumb.
        $parent_link = Link ::createFromRoute($parent_name, 'entity.node.canonical', ['node' => $parent_id]);
        // Set the parent breadcrumb.
        $breadcrumb->addLink($parent_link);
      }
    }

    // Return breadcrumbs, current page breadcrumb is added by default.
    return $breadcrumb;
  }

}
