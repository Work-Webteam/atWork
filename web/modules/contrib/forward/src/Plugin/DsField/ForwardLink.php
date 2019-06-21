<?php

namespace Drupal\forward\Plugin\DsField;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\forward\ForwardAccessCheckerInterface;
use Drupal\forward\ForwardLinkBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Forward link plugin.
 *
 * @DsField(
 *   id = "forward_link",
 *   title = @Translation("Forward link"),
 *   entity_type = "node"
 * )
 */
class ForwardLink extends DsFieldBase implements ContainerFactoryPluginInterface {

  /**
   * The access checker service.
   *
   * @var \Drupal\forward\ForwardAccessCheckerInterface
   */
  protected $accessChecker;

  /**
   * The link builder service.
   *
   * @var \Drupal\forward\ForwardLinkBuilderInterface
   */
  protected $linkBuilder;

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
   * @param \Drupal\forward\ForwardLinkBuilderInterface $link_builder
   *   The Forward link builder interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The core configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ForwardAccessCheckerInterface $access_checker, ForwardLinkBuilderInterface $link_builder, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->accessChecker = $access_checker;
    $this->linkBuilder = $link_builder;

    // Force standard render since inline render is part of the "Links" DS element.
    $settings = $config_factory->get('forward.settings')->get();
    $settings['forward_link_inline'] = FALSE;
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
      $container->get('forward.link_builder'),
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

    // Build the link unless Forward is rendering an email.
    $config = $this->getConfiguration();
    if (empty($config['build']['#forward_build'])) {
      $render_array = $this->linkBuilder->buildForwardEntityLink($this->entity(), $this->settings);
    }

    return $render_array;
  }

}
