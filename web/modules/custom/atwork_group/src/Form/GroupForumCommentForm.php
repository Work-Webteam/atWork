<?php

namespace Drupal\atwork_group\Form;

use Drupal\comment\CommentForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Extend comment form save to redirect group forum comments.
 *
 * @internal
 */
class GroupForumCommentForm extends CommentForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    parent::save($form, $form_state);

    $previousUrl = \Drupal::request()->server->get('HTTP_REFERER');
    $parts = explode("/", $previousUrl);
    if ($parts[3] == "group") {
      $group_id = $parts[4];
      if ($parts[5] == "topic") {
        $node_id = $parts[6];
        $form_state->setRedirectUrl(Url::fromRoute('group.forum.topic', array('group' => $group_id, 'node' => $node_id)));
      }
    }
  }

}
