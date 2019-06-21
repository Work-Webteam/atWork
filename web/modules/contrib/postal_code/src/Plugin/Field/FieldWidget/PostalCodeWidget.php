<?php

namespace Drupal\postal_code\Plugin\Field\FieldWidget;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\postal_code\PostalCodeValidationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'postal_code' widget.
 *
 * @FieldWidget(
 *   id = "postal_code_any_postal_code_form",
 *   module = "postal_code",
 *   label = @Translation("Postal Code: Any Format"),
 *   field_types = {
 *     "postal_code"
 *   }
 * )
 */
class PostalCodeWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Config factory service.
   *
   * @var \Drupal\postal_code\Plugin\Field\FieldWidget\PostalCodeWidget
   */
  protected $config;

  /**
   * The PostalCodeValidation service.
   *
   * @var \Drupal\postal_code\PostalCodeValidation
   */
  protected $postalCodeValidation;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The 'postal_code.settings' config.
   * @param \Drupal\postal_code\PostalCodeValidationInterface $postal_code_validation
   *    The PostalCodeValidation service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactoryInterface $config_factory, PostalCodeValidationInterface $postal_code_validation) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->config = $config_factory->get('postal_code.settings');
    $this->postalCodeValidation = $postal_code_validation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('postal_code.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += array(
      '#type' => 'textfield',
      '#default_value' => $value,
      '#size' => 16,
      '#maxlength' => 16,
      '#element_validate' => array(
        array($this, 'validate'),
      ),
      '#description' => $this->t('Select country for validation'),
    );
    return array('value' => $element);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();
    $validator = $this->postalCodeValidation;
    $config = $this->config;
    $value = trim($element['#value']);

    if (!empty($value) && $config->get('validate')) {
      // Locate 'postal_type' in the form.
      $country_code = $field_settings['country_select'];

      if (!empty($country_code)) {
        if ($country_code != 'any') {
          $error_array = $validator->validate($country_code, $value);
        }
        else {
          $validatable_countries = $config->get('valid_countries');
          foreach ($validatable_countries as $key => $country) {
            $err_array[] = $validator->validate($country, $value);
          }
          foreach ($err_array as $k => $v) {
            $error_array[] = $v[0];
          }
        }
      }
      else {
        $form_state->setError($element, $this->t('This form has been altered in a way in which Postal Code validation will not work, but the validation option remains enabled. Please correct the changes to the form or disable the validation option.'));
      }

      if (!in_array(TRUE, $error_array)) {
        $form_state->setError($element, $this->t('Invalid Postal Code Provided.'));
      }
    }
  }
}
