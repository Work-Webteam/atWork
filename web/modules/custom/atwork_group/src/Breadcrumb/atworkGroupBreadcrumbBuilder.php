<?php

namespace Drupal\atwork_group\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Group;
use Drupal\Core\Link;

/**
 * Define class and implement BreadcrumbBuilderInterface.
 */
class atworkGroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   *
   * @return bool
   *   Must return a true or False
   */
  public function applies(RouteMatchInterface $attributes) {
    // Get all parameters.
    $parameters = $attributes->getParameters()->all();

    // Is this a view page for group content?
    if (isset($parameters['view_id']) && $parameters['view_id'] == 'related_content' && ($parameters['display_id'] == 'page_2' || $parameters['display_id'] == 'page_1')) {
      return TRUE;
    }

    // Is this a group landing page?
    if (isset($parameters['group']) && $parameters['group']->getGroupType()
      ->id() == "atwork_groups") {
      return TRUE;
    }

    // Determine if the current page is a Group Photos or Group Post page.
    $is_node = isset($parameters['node']);
    $node_params_set = !empty($parameters['node']);
    $is_photo_gallery = ($is_node && $node_params_set ? $parameters['node']->get('type')
      ->getValue()[0]['target_id'] == 'photos' : FALSE);
    $is_group_post = ($is_node && $node_params_set ? $parameters['node']->get('type')
      ->getValue()[0]['target_id'] == 'group_post' : FALSE);
    if ($is_node && $node_params_set && (($is_photo_gallery) || ($is_group_post))) {
      return TRUE;
    }

    // If this doesn't apply to the route, return false.
    return FALSE;
  }
	
	/**
	 * {@inheritdoc}
	 */
	public function build(RouteMatchInterface $route_match) {
		
		// Define a new object of type Breadcrumb
		$breadcrumb = new Breadcrumb();
		
		// Build out the breadcrumb
		// Add a link to the homepage as our first crumb.
		$breadcrumb->addLink(Link ::createFromRoute('Home', '<front>'));

		// If this is a view page (ie related content view) handle it differently than a node type.
		if((\Drupal::routeMatch()->getParameter('view_id')) && (\Drupal::routeMatch()->getParameter('view_id') == "related_content"))  {
		  // Add link to groups view page
		  $breadcrumb->addLink(Link::createFromRoute(t('Groups'), 'view.atwork_groups.page_1'));
		  
		  $url_param = str_replace('-', ' ', \Drupal::routeMatch()->getParameter('arg_0'));
		  $group_data = '';
		  
		  try {
		  	// Select like history from db
		  	$connection = \Drupal::database();
		  	$query = $connection->query("Select label, id from groups_field_data");
		  	$group_data = $query->fetchAll();
		  }
		  catch(Exception $e) {
		  	\Drupal::logger('type')->error($e->getMessage());
		  }
		  
		  foreach($group_data as $group) {
		  	if (strtolower($url_param) == strtolower($group->label)) {
		  		$group_data = $group;
		  	}
		  }
		  $group = Group::load(\Drupal::routeMatch()->getParameter('arg_0'));
		  $breadcrumb->addLink(Link::createFromRoute(t($group_data->label), 'entity.group.canonical', ['group' => $group_data->id]));
		  
		  return $breadcrumb;
		}
		
		// Get the route parameter for the current page
		if(\Drupal::routeMatch()->getParameter('node')) {
			$route_param = \Drupal::routeMatch()->getParameter('node');
		} else if (\Drupal::routeMatch()->getParameter('group')) {
			$route_param = \Drupal::routeMatch()->getParameter('group');
		}
		
		//// Special handling based on node type aka bundle
		//// NOTE use of the Link class
		switch ( $route_param->bundle() ) {
			case 'photos':
				$gid = '';
				foreach (GroupContent::loadByEntity($route_param) as $group_content) {
					$gid = $group_content->getGroup()->id();
				}
				$group = Group::load($gid);
				
				// Add link to groups view page
				$breadcrumb->addLink(Link::createFromRoute(t('Groups'), 'view.atwork_groups.page_1'));

				// Add Group Name 
				$breadcrumb->addLink(Link::createFromRoute(t($group->label()), 'entity.group.canonical', ['group' => $group->id()]));
				
				// Add Group Galleries page. The param 'arg_0' is the value passed to the view for the contextual filter.
				$breadcrumb->addLink(Link::createFromRoute(t('Photo Galleries'), 'view.related_content.page_2', ['arg_0' => strtolower(str_replace(' ', '-', $group->label()))]));
		 		break;
			case 'group_post':
				$gid = '';
				foreach (GroupContent::loadByEntity($route_param) as $group_content) {
					$gid = $group_content->getGroup()->id();
				}
				$group = Group::load($gid);

				// Add link to groups view page
				$breadcrumb->addLink(Link::createFromRoute(t('Groups'), 'view.atwork_groups.page_1'));
				
				// Add Group Name 
				$breadcrumb->addLink(Link::createFromRoute(t($group->label()), 'entity.group.canonical', ['group' => $group->id()]));
				
				// Add Group Galleries page. The param 'arg_0' is the value passed to the view for the contextual filter.
				$breadcrumb->addLink(Link::createFromRoute(t('Posts'), 'view.related_content.page_1', ['arg_0' => strtolower(str_replace(' ', '-', $group->label()))]));
		 		break;
			case 'atwork_groups':
				// Add link to groups view page
				$breadcrumb->addLink(Link::createFromRoute(t('Groups'), 'view.atwork_groups.page_1'));
		 		break;
		}
		
		// Don't forget to add cache control by a route.
		// Otherwise all pages will have the same breadcrumb.
		$breadcrumb->addCacheContexts(['route']);
		
		// Return breadcrumb
		return $breadcrumb;
	}
	
}
