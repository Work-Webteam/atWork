<?php

namespace Drupal\atwork_group\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'group machine name' formatter.
 *
 * @FieldFormatter(
 *   id = "group_machine_name",
 *   label = @Translation("Group Machine Name"),
 *   description = @Translation("Display group title machine name."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class GroupMachineNameFormatter extends FormatterBase  {

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
          // $element[$delta] = ['#markup' => $clean_string];
          $elements[$delta] = [
            '#type' => 'inline_template',
            '#template' => '{{ value|nl2br }}',
            '#context' => ['value' => $clean_string."xXx"],
          ];
        }
      }
    }
    return $element;
  }


}
