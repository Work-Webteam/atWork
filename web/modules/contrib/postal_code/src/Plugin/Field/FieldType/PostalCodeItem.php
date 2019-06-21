<?php

namespace Drupal\postal_code\Plugin\Field\FieldType;


use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Plugin implementation of the 'postal_code' field type.
 *
 * @FieldType(
 *   id = "postal_code",
 *   label = @Translation("Postal Code"),
 *   module = "postal_code",
 *   description = @Translation("Postal Code field."),
 *   default_widget = "postal_code_any_postal_code_form",
 *   default_formatter = "postal_code_simple_text"
 * )
 */
class PostalCodeItem extends FieldItemBase {

  /**
   * The PostalCodeValidation service.
   *
   * @var \Drupal\postal_code\PostalCodeValidation
   */
  protected $postalCodeValidation;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->postalCodeValidation = \Drupal::getContainer()->get('postal_code.validator');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = array(
        'country_select' => 'any',
      ) + parent::defaultFieldSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Postal code'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getSettings();

    $options = array('any' => (string) $this->t('Any'));
    $postal_code_validation_data = $this->postalCodeValidation->getValidationPatterns();

    $countrylist = CountryManager::getStandardList();

    foreach ($postal_code_validation_data as $country => $regex) {
      $options[$country] = $countrylist[Unicode::strtoupper($country)]->render();
    }

    $value = isset($settings['country_select']) ? $settings['country_select'] : 'any';

    $element['country_select'] = array(
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $options,
      '#default_value' => $value,
      '#description' => $this->t('Select country for validation'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type'      => 'varchar',
          'length'    => '16',
        ),
      ),
    );
  }
}
