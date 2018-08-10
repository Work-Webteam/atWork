<?php

namespace Drupal\insert\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\insert\Utility\InsertFileWidgetUtility;
use Drupal\insert\Utility\InsertImageWidgetUtility;

/**
 * Plugin implementation of the Insert Image widget.
 * This is just a barebone set of overwritten methods. All actual logic is
 * deferred to \Drupal\insert\Utility\UtilityImage.
 *
 * @FieldWidget(
 *   id = "insert_image",
 *   module = "insert",
 *   label = @Translation("Image Insert"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class InsertImageWidget extends ImageWidget {

  /**
   * @var InsertImageWidgetUtility|null
   */
  protected static $util;

  /**
   * @return InsertImageWidgetUtility
   */
  protected static function util() {
    if (self::$util === null) {
      self::$util = new InsertImageWidgetUtility();
    }
    return self::$util;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::util()->defaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    return self::util()->settingsForm($element, $this->getSettings());
  }

  /**
   * @see InsertFileWidgetUtility::validateStyles
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormState $form_state
   */
  public static function validateStyles($element, &$form_state) {
    self::util()->validateStyles($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element = self::util()->formElement($element, $this->getSettings());
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $originalElement = $element;
    $element = self::util()->process($element, $form_state);

    return $element === null
      ? parent::process($originalElement, $form_state, $form)
      : parent::process($element, $form_state, $form);
  }

}

