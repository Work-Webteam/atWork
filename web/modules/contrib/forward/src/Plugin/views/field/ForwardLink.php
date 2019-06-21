<?php

namespace Drupal\forward\Plugin\views\field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\forward\ForwardAccessCheckerInterface;
use Drupal\forward\ForwardLinkBuilderInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("forward_link")
 */
class ForwardLink extends FieldPluginBase implements ContainerFactoryPluginInterface {

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
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Views fields do not have a view mode, so skip that in the access check.
    $view_mode = FALSE;
    if ($this->accessChecker->isAllowed($this->settings, $values->_entity, $view_mode)) {
      return $this->linkBuilder->buildForwardEntityLink($values->_entity, $this->settings);
    }
  }

}
