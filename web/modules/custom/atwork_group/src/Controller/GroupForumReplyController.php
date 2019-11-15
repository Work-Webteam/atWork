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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\forum\ForumManagerInterface;
use Drupal\taxonomy\TermInterface;


/**
 * @file
 * Contains \Drupal\atwork_group\Controller\GroupForumTopicController
 */

namespace Drupal\atwork_group\Controller;

use Drupal\comment\Controller\CommentController;

class GroupForumReplyController extends CommentController {

  /**
   * Form constructor for the group forum comment reply form.
   *
   * There are several cases that have to be handled, including:
   *   - replies to comments
   *   - replies to entities
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An associative array containing:
   *   - An array for rendering the entity or parent comment.
   *     - comment_entity: If the comment is a reply to the entity.
   *     - comment_parent: If the comment is a reply to another comment.
   *   - comment_form: The comment form as a renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function getGroupForumReplyForm(\Symfony\Component\HttpFoundation\Request $request, \Drupal\Core\Entity\EntityInterface $entity, $field_name, $pid = NULL)  {
    return $this->getReplyForm($request, $entity, $field_name, $pid) ;
  }


  public function groupForumReplyFormAccess(\Drupal\Core\Entity\EntityInterface $entity, $field_name, $pid = NULL) {
    return $this->replyFormAccess($entity, $field_name, $pid);
  }

}

?>
