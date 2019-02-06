<?php

// Define Class Namespace
namespace Drupal\atwork_group\Breadcrumb;

// Use namespaces for required classes
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Group;
use Drupal\Core\Link;

// Define class and implement BreadcrumbBuilderInterface
class atworkGroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {
	/**
	 * {@inheritdoc}
	 */
	public function applies(RouteMatchInterface $attributes) {
	//	// You must return a BOOLEAN TRUE or FALSE.
		// Get all parameters
		$parameters = $attributes->getParameters()->all();
		
		// Determine if the current page is a Group Photos page
		$is_node = isset($parameters['node']);
		$node_params_set = !empty ($parameters['node']);
		$is_photo_gallery = ( $is_node && $node_params_set ? $parameters['node']->get('type')->getValue()[0]['target_id'] == 'photos' : FALSE);
		$is_group_post = ( $is_node && $node_params_set ?  $parameters['node']->get('type')->getValue()[0]['target_id'] == 'group_post' : FALSE);
		
		if ($is_node && $node_params_set && ( ($is_photo_gallery) || ($is_group_post) )) {
			return TRUE;
		}
		
		// Still here? This does not apply.
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
		
		// Get the node/url for the current page
		$node = \Drupal::routeMatch()->getParameter('node');
		$url_components = explode('/',ltrim($node->toUrl()->toString(), '/'));
		
		//// Special handling based on node type aka bundle
		//// NOTE use of the Link class
		switch ( $node->bundle() ) {
			case 'photos':
				$gid = '';
				foreach (GroupContent::loadByEntity($node) as $group_content) {
					$gid = $group_content->getGroup()->id();
				}
				$group = Group::load($gid);

				// Add Group Name 
				$breadcrumb->addLink(Link ::createFromRoute(t($group->label()), 'entity.group.canonical', ['group' => $gid]));
				
				// Add Group Galleries page. The param 'arg_0' is the value passed to the view for the contextual filter.
				$breadcrumb->addLink(Link ::createFromRoute(t('Photo Galleries'), 'view.related_content.page_2', ['arg_0' => $gid]));
		 		break;
			case 'group_post':
				$gid = '';
				foreach (GroupContent::loadByEntity($node) as $group_content) {
					$gid = $group_content->getGroup()->id();
				}
				$group = Group::load($gid);
				
				// Add Group Name 
				$breadcrumb->addLink(Link ::createFromRoute(t($group->label()), 'entity.group.canonical', ['group' => $gid]));
				
				// Add Group Galleries page. The param 'arg_0' is the value passed to the view for the contextual filter.
				$breadcrumb->addLink(Link ::createFromRoute(t('Posts'), 'view.related_content.page_1', ['arg_0' => $gid]));
				
		 		break;
		}
		//
		//// Don't forget to add cache control by a route.
		//// Otherwise all pages will have the same breadcrumb.
		//$breadcrumb->addCacheContexts(['route']);
		//
		//// Return object of type breadcrumb
		
		return $breadcrumb;
	}
	
}