<?php

// Define Class Namespace
namespace Drupal\atwork_group\Breadcrumb;

// Use namespaces for required classes
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
		if (isset ($parameters['node']) && !empty ($parameters['node']) && $parameters['node']->get('type')->getValue()[0]['target_id'] == 'photos') {
			return TRUE;
		}
		
		// Still here? This does not apply.
		return FALSE;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function build(RouteMatchInterface $route_match) {
		ksm($route_match);
		//// Define a new object of type Breadcrumb
		$breadcrumb = new Breadcrumb();
		
		// Build out the breadcrumb
		// Add a link to the homepage as our first crumb.
		$breadcrumb->addLink(Link ::createFromRoute('Home', '<front>'));
		
		// Get the node for the current page
		$node = \Drupal::routeMatch()->getParameter('node');
		ksm($node);
		
		//// Special handling based on node type aka bundle
		//// NOTE use of the Link class
		//switch ( $node->bundle() ) {
		//	case 'project':
		//		$breadcrumb->addLink(Link ::createFromRoute('Project List Page', 'view.article.page_1'));
		//		break;
		//		
		//	case 'article':
		//		$breadcrumb->addLink(Link ::createFromRoute('Article List Page', 'view.articles.page_1'));
		//		break;
		//}
		//
		//// Don't forget to add cache control by a route.
		//// Otherwise all pages will have the same breadcrumb.
		//$breadcrumb->addCacheContexts(['route']);
		//
		//// Return object of type breadcrumb
		
		return $breadcrumb;
	}
	
}