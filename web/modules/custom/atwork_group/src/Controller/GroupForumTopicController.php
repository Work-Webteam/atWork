<?php

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\forum\ForumManagerInterface;
use Drupal\taxonomy\TermInterface;


/**
  * @file
  * Contains \Drupal\atwork_group\Controller\GroupForumTopicController
  */

namespace Drupal\atwork_group\Controller;

use Drupal\node\Controller\NodeViewController;

class GroupForumTopicController extends NodeViewController {

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
  public function groupForumTopicView(\Drupal\group\Entity\GroupInterface $group, \Drupal\Core\Entity\EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    return $this->view($node, $view_mode, $langcode);
  }


}

?>
