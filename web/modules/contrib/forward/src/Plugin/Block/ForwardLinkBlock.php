<?php

namespace Drupal\forward\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\forward\ForwardAccessCheckerInterface;
use Drupal\forward\ForwardLinkBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with a Forward link.
 *
 * @Block(
 *   id = "forward_link_block",
 *   admin_label = @Translation("Forward Link"),
 *   category = @Translation("Content")
 * )
 */
class ForwardLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The link builder service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity being forwarded.
   *
   * @var Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The settings used for this plugin instance.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs a new ForwardLinkBlock object.
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The core route matcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ForwardAccessCheckerInterface $access_checker, ForwardLinkBuilderInterface $link_builder, ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->accessChecker = $access_checker;
    $this->linkBuilder = $link_builder;
    $this->routeMatch = $route_match;

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
      $container->get('config.factory'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return $this->isAllowed() ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    $allowed = FALSE;
    $entity = NULL;
    $bundle = NULL;

    $parameters = $this->routeMatch->getParameters();
    if ($parameters->has('node')) {
      $entity = $parameters->get('node');
      $bundle = $entity->bundle();
    }
    if ($parameters->has('taxonomy_term')) {
      $entity = $parameters->get('taxonomy_term');
      $bundle = $entity->bundle();
    }
    if ($parameters->has('user')) {
      $entity = $parameters->get('user');
      $bundle = '';
    }

    if ($entity && $entity instanceof EntityInterface) {
      $view_mode = 'full';
      $this->entity = $entity;
      $allowed = $this->accessChecker->isAllowed($this->settings, $entity, $view_mode, $entity->getEntityTypeId(), $bundle);
    }

    return $allowed;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render_array = [];

    // Build the link unless Forward is rendering an email.
    $config = $this->getConfiguration();
    if (empty($config['build']['#forward_build']) && $this->isAllowed()) {
      $render_array = $this->linkBuilder->buildForwardEntityLink($this->entity, $this->settings);
    }

    return $render_array;
  }

}
