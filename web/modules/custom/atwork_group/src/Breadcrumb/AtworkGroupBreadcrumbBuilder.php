<?php

namespace Drupal\atwork_group\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Group;
use Drupal\taxonomy\Entity\Term;


/**
 * Define class and implement BreadcrumbBuilderInterface.
 */
class AtworkGroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {

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

    // Is this a group forum topics listing or topic page?
    if ($attributes->getRouteName() == 'group.forum' || $attributes->getRouteName() == 'group.forum.topic') {
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
    // Define a new object of type Breadcrumb.
    $breadcrumb = new Breadcrumb();

    // Build out the breadcrumb
    // Add a link to the homepage as our first crumb.
    $breadcrumb->addLink(Link ::createFromRoute('Home', '<front>'));

    // If this is a view page (ie related content view)
    // handle it differently than a node type.
    if ((\Drupal::routeMatch()->getParameter('view_id')) && (\Drupal::routeMatch()->getParameter('view_id') == "related_content")) {
      // Add link to groups view page.
      $url_param = str_replace('-', ' ', \Drupal::routeMatch()->getParameter('arg_0'));
      $group_data = '';

      try {
        // Select like history from db.
        $connection = \Drupal::database();
        $query = $connection->query("Select label, id from groups_field_data");
        $group_data = $query->fetchAll();
      }
      catch (Exception $e) {
        \Drupal::logger('type')->error($e->getMessage());
      }

      foreach ($group_data as $group) {
        if (strtolower($url_param) == strtolower($group->label)) {
          $group_data = $group;
        }
      }
      $group = Group::load(\Drupal::routeMatch()->getParameter('arg_0'));
      $breadcrumb->addLink(Link::createFromRoute($group_data->label, 'entity.group.canonical', ['group' => $group_data->id]));

      return $breadcrumb;
    }

    // Get the route parameter for the current page.
    if (\Drupal::routeMatch()->getParameter('node')) {
      $route_param = \Drupal::routeMatch()->getParameter('node');
    }
    elseif (\Drupal::routeMatch()->getParameter('group')) {
      $route_param = \Drupal::routeMatch()->getParameter('group');
    }
    else {
      $route_param = NULL;
    }

    // Special handling based on node type aka bundle
    // NOTE use of the Link class.
    switch ($route_param->bundle()) {
      case 'photos':
        $gid = '';
        foreach (GroupContent::loadByEntity($route_param) as $group_content) {
          $gid = $group_content->getGroup()->id();
        }
        $group = Group::load($gid);

        // Add link to groups view page.
        $breadcrumb->addLink(Link::createFromRoute(t('Groups'), 'view.atwork_groups.page_1'));

        // Add Group Name.
        $breadcrumb->addLink(Link::createFromRoute($group->label(), 'entity.group.canonical', ['group' => $group->id()]));

        // Add Group Galleries page. The param 'arg_0' is the value
        // passed to the view for the contextual filter.
        $clean_label = strtolower(str_replace(' ', '-', $group->label()));
        $breadcrumb
          ->addLink(Link::createFromRoute(t('Photo Galleries'), 'view.related_content.page_2', ['arg_0' => $clean_label])
        );
        break;

      case 'group_post':
        $gid = '';
        foreach (GroupContent::loadByEntity($route_param) as $group_content) {
          $gid = $group_content->getGroup()->id();
        }
        $group = Group::load($gid);

        // Add link to groups view page.
        $breadcrumb->addLink(Link::createFromRoute(t('Groups'), 'view.atwork_groups.page_1'));

        // Add Group Name.
        $breadcrumb->addLink(Link::createFromRoute($group->label(), 'entity.group.canonical', ['group' => $group->id()]));

        // Add Group Galleries page.
        // The param 'arg_0' is the value passed
        // to the view for the contextual filter.
        $clean_label = strtolower(str_replace(' ', '-', $group->label()));
        $breadcrumb->addLink(Link::createFromRoute(t('Posts'), 'view.related_content.page_1', ['arg_0' => $clean_label]));
        break;

      case 'atwork_groups':
        // Add link to groups view page.
        $breadcrumb->addLink(Link::createFromRoute(t('Groups'), 'view.atwork_groups.page_1'));
        break;
    }

    if ($route_match->getRouteName() == 'group.forum') {
      // Add link to Group.
      $group = $route_match->getParameter('group');
      $forum = $route_match->getParameter('taxonomy_term');
      $breadcrumb->addLink(Link::createFromRoute($group->label(), 'entity.group.canonical', ['group' => $group->id()]));

      // Add link to Forum Container if container has multiple forums
      if (empty($forum->forum_container->value)) {
        $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($forum->id());
        $parent = reset($parent);
        $forum_manager = \Drupal::service('forum_manager');
        $forums = $forum_manager->getChildren(\Drupal::config('forum.settings')->get('vocabulary'), $parent->id());
        if (count($forums) > 1) {
          $breadcrumb->addLink(Link::createFromRoute($parent->label(), 'group.forum', ['group' => $group->id(), 'taxonomy_term' => $parent->id()]));
        }
      }
    }

    if ($route_match->getRouteName() == 'group.forum.topic') {
      $group = $route_match->getParameter('group');
      $topic = $route_match->getParameter('node');

      // Add link to groups view page.
      $breadcrumb->addLink(Link::createFromRoute(t('Groups'), 'view.atwork_groups.page_1'));

      // Add link to group.
      $breadcrumb->addLink(Link::createFromRoute($group->label(), 'entity.group.canonical', ['group' => $group->id()]));

      $forum_id = $topic->get('taxonomy_forums')->target_id;
      $term = Term::load($forum_id);

      // Add link to Forum Container if container has multiple forums
      if (empty($term->forum_container->value)) {
        $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term->id());
        $parent = reset($parent);
        $forum_manager = \Drupal::service('forum_manager');
        $forums = $forum_manager->getChildren(\Drupal::config('forum.settings')->get('vocabulary'), $parent->id());
        if (count($forums) > 1) {
          $breadcrumb->addLink(Link::createFromRoute($parent->label(), 'group.forum', ['group' => $group->id(), 'taxonomy_term' => $parent->id()]));
        }
      }

      // Add link to forum breadcrumb
      $breadcrumb->addLink(Link::createFromRoute($term->getName(), 'group.forum', ['group' => $group->id(), 'taxonomy_term' => $forum_id]));
    }

    // Don't forget to add cache control by a route.
    // Otherwise all pages will have the same breadcrumb.
    $breadcrumb->addCacheContexts(['route']);

    // Return breadcrumb.
    return $breadcrumb;
  }

}
