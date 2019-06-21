<?php

namespace Drupal\likeit\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide an entity view field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("likeit_target_entity_view_views_field")
 */
class LikeItTargetEntityViewViewsField extends FieldPluginBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a LikeItTargetEntityViewViewsField object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_alter_empty'] = ['default' => FALSE];
    $options['view_mode'] = ['default' => 'teaser'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $storage = $this->entityTypeManager->getStorage('entity_view_mode');
    $query = $storage->getQuery()
      ->condition('targetEntityType', 'node')
      ->execute();

    $views_modes = $storage->loadMultiple($query);

    $node_view_modes = [];

    if (!empty($views_modes)) {
      foreach ($views_modes as $view_mode) {
        $key = str_replace('node.', '', $view_mode->id());
        $node_view_modes[$key] = $view_mode->label();
      }
    }

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#default_value' => $this->options['view_mode'],
      '#options' => $node_view_modes,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (empty($values->_entity)) {
      return '';
    }

    $entity = $values->_entity;
    $entity_target_type = $entity->getTargetEntityType();
    $entity_target_id = $entity->getTargetEntityId();

    $target_entity = $this->entityTypeManager
      ->getStorage($entity_target_type)
      ->load($entity_target_id);

    if (!empty($target_entity)) {
      $view_builder = $this->entityTypeManager
        ->getViewBuilder($entity_target_type);

      $view_mode = $this->options['view_mode'];
      $output = $view_builder->view($target_entity, $view_mode);

      return render($output);
    }

    return FALSE;
  }

}
