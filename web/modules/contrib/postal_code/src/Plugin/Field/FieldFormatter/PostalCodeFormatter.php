<?php

namespace Drupal\postal_code\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'postal_code' formatter.
 *
 * @FieldFormatter(
 *   id = "postal_code_simple_text",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "postal_code"
 *   }
 * )
 */
class PostalCodeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();

    foreach ($items as $delta => $item) {
      $element[$delta] = array(
        '#type' => 'markup',
        '#markup' => $item->value,
      );
    }

    return $element;
  }
}
