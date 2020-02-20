<?php

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\forum\ForumManagerInterface;
use Drupal\taxonomy\TermInterface;


/**
  * @file
  * Contains \Drupal\atwork_group\Controller\GroupForumController
  */

namespace Drupal\atwork_group\Controller;

// use Drupal\Core\Controller\ControllerBase;
use Drupal\forum\Controller\ForumController;

class GroupForumController extends ForumController {

  protected $currentGroup;

  /**
   * Returns group forum page for a group forum.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The forum to render the page for.
   *
   * @return array
   *   A render array.
   */
  public function groupForumPage(\Drupal\group\Entity\GroupInterface $group, \Drupal\taxonomy\TermInterface $taxonomy_term) {
    $this->currentGroup = $group;
    return $this->forumPage($taxonomy_term);
  }

  public function groupForumTitle(\Drupal\group\Entity\GroupInterface $group, \Drupal\taxonomy\TermInterface $taxonomy_term) {

    return [
      // '#markup' => $group->label->value . " - " . $taxonomy_term->getName(),
      '#markup' => $taxonomy_term->getName(),
      '#allowed_tags' => \Drupal\Component\Utility\Xss::getHtmlTagList()
    ];
  }

  protected function buildActionLinks($vid, \Drupal\taxonomy\TermInterface $forum_term = NULL) {
    $user = $this->currentUser();

    $links = [];
    // Loop through all bundles for forum taxonomy vocabulary field.
    foreach ($this->fieldMap['node']['taxonomy_forums']['bundles'] as $type) {
      if ($this->nodeAccess->createAccess($type)) {
        $node_type = $this->nodeTypeStorage->load($type);
        $group_title = strtolower(str_replace(' ', '-', $this->currentGroup->label()));
        $links[$type] = [
          '#attributes' => ['class' => ['action-links']],
          '#theme' => 'menu_local_action',
          '#link' => [
            'title' => $this->t('Add Forum topic', [
              '@node_type' => $this->nodeTypeStorage->load($type)->label(),
            ]),
            'url' => \Drupal\Core\Url::fromUri('internal:/group/' . $group_title .'/content/create/group_node:forum'),
          ],
          '#cache' => [
            'tags' => $node_type->getCacheTags(),
          ],
        ];
      }
    }
    if (empty($links)) {
      // Authenticated user does not have access to create new topics.
      if ($user->isAuthenticated()) {
        $links['disallowed'] = [
          '#markup' => $this->t('You are not allowed to post new content in the forum.'),
        ];
      }
      // Anonymous user does not have access to create new topics.
      else {
        $links['login'] = [
          '#attributes' => ['class' => ['action-links']],
          '#theme' => 'menu_local_action',
          '#link' => [
            'title' => $this->t('Log in to post new content in the forum.'),
            'url' => Url::fromRoute('user.login', [], ['query' => $this->getDestinationArray()]),
          ],
        ];
      }
    }
    return $links;
  }
}

?>
