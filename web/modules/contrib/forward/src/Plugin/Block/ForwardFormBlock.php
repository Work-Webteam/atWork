<?php

namespace Drupal\forward\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\forward\ForwardAccessCheckerInterface;
use Drupal\forward\ForwardFormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with a Forward form.
 *
 * @Block(
 *   id = "forward_form_block",
 *   admin_label = @Translation("Forward Form"),
 *   category = @Translation("Forms")
 * )
 */
class ForwardFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\forward\ForwardFormBuilderInterface $form_builder
   *   The Forward form builder interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The core configuration factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The core route matcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ForwardAccessCheckerInterface $access_checker, ForwardFormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->accessChecker = $access_checker;
    $this->formBuilder = $form_builder;
    $this->routeMatch = $route_match;

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
      $container->get('config.factory'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#description' => $this->t('If set, this is placed in the block before the form.'),
    ];

    if (isset($config['body']['value'])) {
      $form['body']['#default_value'] = $config['body']['value'];
      $form['body']['#format'] = $config['body']['format'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['body'] = $form_state->getValue('body');
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

    $parameters = $this->routeMatch->getParameters();
    if ($parameters->has('node')) {
      $entity = $parameters->get('node');
      $bundle = $entity->bundle();
    }
    if ($parameters->has('commerce_product')) {
      $entity = $parameters->get('commerce_product');
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

    if ($entity) {
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

    // Build the form unless Forward is rendering an email.
    $config = $this->getConfiguration();
    if (empty($config['build']['#forward_build']) && $this->isAllowed()) {
      $render_array = [];
      if (!empty($config['body']['value'])) {
        $render_array[] = [
          '#type' => 'processed_text',
          '#text' => $config['body']['value'],
          '#format' => $config['body']['format'],
        ];
      }
      $render_array[] = $this->formBuilder->buildForwardEntityForm($this->entity, $this->settings);
    }

    return $render_array;
  }

}
