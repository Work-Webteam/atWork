<?php

namespace Drupal\atwork_group\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'group forum link' formatter.
 *
 * @FieldFormatter(
 *   id = "group_forum_link",
 *   label = @Translation("Group Forum Link"),
 *   description = @Translation("Display a link to the current group forum."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class GroupForumLinkFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $group = \Drupal::routeMatch()->getParameter('group');

    foreach ($items as $delta => $item) {
      // Render each element as markup.

      // get forums in container
      $forum_manager = \Drupal::service('forum_manager');
      $item->entity->forums = $forum_manager->getChildren(\Drupal::config('forum.settings')->get('vocabulary'), $item->entity->id());

      // if only one forum then link directly to forum, otherwise link to container
      if (count($item->entity->forums) == 1) {
        reset($item->entity->forums);
        $forum_id = key($item->entity->forums);
      }
      else {
        $forum_id = $item->entity->id();
      }
      if ($group) {
        $element[$delta] = ['#markup' => '<a href="/group/' . $group->id() . '/forum/' . $forum_id . '"> Forum </a>'];
      }
      else {
        $element[$delta] = ['#markup' => '<a href="/forum/' . $forum_id . '"> Forum </a>'];
      }
    }

    return $element;
  }


}
