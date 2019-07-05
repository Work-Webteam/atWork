<?php

namespace Drupal\atwork_program_pages\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'program_area_menu' block.
 *
 * @Block(
 *  id = "program_area_menu",
 *  admin_label = @Translation("Program Area Menu"),
 * )
 */
class program_area_menu extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#cache' => ['contexts' => ['url.path']],
    ];
    // We will pass back blank markup
    // if there is no related menu.
    $build['program_area_menu']['#markup'] = '';
    // Get the current path so we can load the node we are looking at.
    $current_path = \Drupal::service('path.current')
      ->getPath();
    // Get the path alias in case we use
    // different paths in the future.
    $alias = \Drupal::service('path.alias_manager')
      ->getPathByAlias($current_path);
    // This should give us all route parameters, including the node id.
    $params = Url::fromUri("internal:" . $alias)->getRouteParameters();
    // Node id is the value.
    $nid = current($params);
    // Load this node.
    $program_page = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($nid);
    // Now that we have this node, we can check the
    // 'field_parent_program' field which will give us
    // the nid of the program landing page.
    $program_type = $program_page
      ->get('field_parent_program')
      ->getValue();
    // If we got a value back i.e. if this
    // program content page was assigned to a
    // program landing page, we pull out THAT NID.
    $program_type_id = isset($program_type) ? current(current($program_type)) : NULL;
    // If we got an NID, then we can load that node and grab the menu item.
    if ($program_type_id != NULL) {
      $program = \Drupal\node\Entity\Node::load($program_type_id);
      // We don't need the markup anymore - no sense carrying it along.
      unset($build['program_area_menu']['#markup']);
      // Load the view so it is wrapped in a render_array.
      $program_menu_view = $program->field_program_area_menu->view();
      // We don't need double titles here -
      // so lets nuke this one and just use the block title.
      $program_menu_view['#title'] = '';
      // Add it to the block, and render it on page.
      $build['program_area_menu'] = $program_menu_view;
    }
    return $build;

  }

}




