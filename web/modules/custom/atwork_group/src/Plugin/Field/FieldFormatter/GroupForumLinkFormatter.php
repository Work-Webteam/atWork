<?php

namespace Drupal\atwork_group\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

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

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = ['#markup' => '<a href="/forum/' . $item->entity->id() . '"> Forum </a>'];
    }

    return $element;
  }


}
