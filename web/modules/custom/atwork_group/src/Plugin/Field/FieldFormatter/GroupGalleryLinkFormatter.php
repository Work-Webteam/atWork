<?php

namespace Drupal\atwork_group\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'group gallery link' formatter.
 *
 * @FieldFormatter(
 *   id = "group_gallery_link",
 *   label = @Translation("Gallery Link"),
 *   description = @Translation("Display link to group photo galleries."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class GroupGalleryLinkFormatter extends FormatterBase  {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $group = \Drupal::routeMatch()->getParameter('group');

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      if ($group) {
        $clean_string = \Drupal::service('pathauto.alias_cleaner')->cleanString($item->value);
        if ($group) {
          $element[$delta] = ['#markup' => '<a href="/groups/' . $clean_string . '/photo-galleries"> Photo Galleries </a>'];
        }
      }
    }

    return $element;
  }


}
