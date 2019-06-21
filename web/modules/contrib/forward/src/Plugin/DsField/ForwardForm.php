<?php

namespace Drupal\forward\Plugin\DsField;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\forward\ForwardAccessCheckerInterface;
use Drupal\forward\ForwardFormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Forward form plugin.
 *
 * @DsField(
 *   id = "forward_form",
 *   title = @Translation("Forward form"),
 *   entity_type = "node"
 * )
 */
class ForwardForm extends DsFieldBase implements ContainerFactoryPluginInterface {

  /**
   * The access checker service.
   *
   * @var \Drupal\forward\ForwardAccessCheckerInterface
   */
  protected $accessChecker;

  /**
   * The form builder service.
   *
   * @var \Drupal\forward\ForwardFormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The settings used for this plugin instance.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs a Display Suite field plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin Id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\forward\ForwardAccessCheckerInterface $access_checker
   *   The Forward access checker interface.
   * @param \Drupal\forward\ForwardFormBuilderInterface $form_builder
   *   The Forward form builder interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The core configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ForwardAccessCheckerInterface $access_checker, ForwardFormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->accessChecker = $access_checker;
    $this->formBuilder = $form_builder;

    // Force the "form" interface.
    $settings = $config_factory->get('forward.settings')->get();
    $settings['forward_interface_type'] = 'form';
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('forward.access_checker'),
      $container->get('forward.form_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    $config = $this->getConfiguration();
    $entity = isset($config['entity']) ? $this->entity() : NULL;
    return $this->accessChecker->isAllowed($this->settings, $entity, $this->viewMode(), $this->getEntityTypeId(), $this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render_array = [];

    // Build the form unless Forward is rendering an email.
    $config = $this->getConfiguration();
    if (empty($config['build']['#forward_build'])) {
      $render_array = $this->formBuilder->buildForwardEntityForm($this->entity(), $this->settings);
    }

    return $render_array;
  }

}
