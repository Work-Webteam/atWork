<?php

namespace Drupal\postal_code\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\postal_code\PostalCodeValidationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Postal code admin settings form.
 */
class PostalCodeSettingsForm extends ConfigFormBase {

  /**
   * The CountryManager service.
   *
   * @var \Drupal\Core\Locale\CountryManager
   */
  protected $countryManager;

  /**
   * The PostalCodeValidation service.
   *
   * @var \Drupal\postal_code\PostalCodeValidation
   */
  protected $postalCodeValidation;

  /**
   * The postal code settings.
   *
   * @var \Drupal\postal_code\Form\PostalCodeSettingsForm
   */
  protected $postalCodeSettings;

  /**
   * Constructs a \Drupal\postal_code\Form\PostalCodeSettingsFor object.
   *
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *    The CountryManager service.
   *
   * @param \Drupal\postal_code\PostalCodeValidationInterface $postal_code_validation
   *    The PostalCodeValidation service.
   */
  public function __construct(CountryManagerInterface $country_manager, PostalCodeValidationInterface $postal_code_validation) {
    $this->countryManager = $country_manager;
    $this->postalCodeValidation = $postal_code_validation;
    $this->postalCodeSettings = $this->config('postal_code.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('country_manager'),
      $container->get('postal_code.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'postal_code_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['postal_code.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $country_list = $this->countryManager->getStandardList();
    $postal_code_validation_data = $this->postalCodeValidation->getValidationPatterns();
    $options = array();

    foreach ($postal_code_validation_data as $country_code => $info) {
      $options[$country_code] = $country_list[Unicode::strtoupper($country_code)];
    }

    $form['postal_code_valid_countries'] = array(
      '#type'           => 'select',
      '#title'          => t('Valid "Any" Countries'),
      '#size'           => 16,
      '#multiple'       => TRUE,
      '#options'        => $options,
      '#default_value'  => $this->postalCodeSettings->get('valid_countries'),
      '#description'    => '<p>' . t('Select the countr(y/ies) for Postal Code Validation for "Any" field type.') . '</p><p><em>' . t('This is most useful when you have a form that allows, for example, US and Canadian addresses.') . '</em></p><p><strong>' . t('VALIDATION ONLY OCCURS IF THE "VALIDATE" CHECKBOX BELOW IS SELECTED.') . '</strong></p>',
    );

    $form['postal_code_validate'] = array(
      '#type'           => 'checkbox',
      '#title'          => t('Validate'),
      '#default_value'  => $this->postalCodeSettings->get('validate'),
      '#description'    => t('Validate submitted postal codes?'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Write settings into config file.
    $this
      ->postalCodeSettings
      ->set('valid_countries', $form_state->getValue('postal_code_valid_countries'))
      ->set('validate', $form_state->getValue('postal_code_validate'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
