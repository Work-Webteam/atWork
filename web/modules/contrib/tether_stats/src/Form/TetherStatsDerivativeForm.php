<?php

namespace Drupal\tether_stats\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Default derivative form to add and edit and derivative.
 */
class TetherStatsDerivativeForm extends EntityForm {

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(QueryFactory $entity_query, EntityManagerInterface $entity_manager) {

    $this->entityQuery = $entity_query;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity.query'),
        $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = [];

    $options = [
      '*' => '- ' . $this->t('Can be applied to any stats element') . ' -',
    ];

    // Extract only the content entities.
    $entity_types = $this->entityManager->getEntityTypeLabels(TRUE);
    $options += reset($entity_types);

    $form['derivativeEntityType'] = [
      '#type' => 'select',
      '#title' => t('Entity Type'),
      '#description' => $this->t('Select an entity type above to restrict the use of this derivative to stat elements for entities of the given type.'),
      '#options' => $options,
      '#default_value' => $this->entity->isNew() ? 'node' : $this->entity->getDerivativeEntityType(),
      '#disabled' => !$this->entity->isNew(),
      '#ajax' => [
        'callback' => 'Drupal\tether_stats\Form\TetherStatsDerivativeForm::selectEntityTypeAjaxCallback',
        'wrapper' => 'tether_stats-bundle-div',
      ],
    ];

    $options = [
      '*' => '- ' . $this->t('Can be applied to any bundle') . ' -',
    ];

    $bundle_info = $this->entityManager->getBundleInfo($form_state->getValue('derivativeEntityType', 'node'));

    foreach ($bundle_info as $bundle => $info) {

      $options[$bundle] = $info['label'];
    }

    $form['derivativeBundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#description' => $this->t('Select the bundle for which to derive a new tracking element set.'),
      '#options' => $options,
      '#default_value' => $this->entity->isNew() ? '*' : $this->entity->getDerivativeBundle(),
      '#disabled' => !$this->entity->isNew(),
      '#states' => [
        // Hide this option unless an entity type is selected in the previous
        // drop down.
        'invisible' => [
          ':input[name="entity_type"]' => ['value' => '*'],
        ],
      ],
      '#prefix' => '<div id="tether_stats-bundle-div">',
      '#suffix' => '</div>',
    ];

    $form['name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Derivative Name'),
      '#description' => $this->t('A unique machine name that identifies this derivative. If this value contains characters other than letter, numbers, "-" or "_" then it will be automatically converted into a proper machine name'),
      '#maxlength' => 32,
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->isNew() ? '' : $this->entity->getId(),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '[^a-z0-9_-]+',
      ],
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    if ($this->entity->save()) {

      $this->messenger()->addMessage(t('Added a new derivative %derivative for the entity type %entity_type. You may now use this derivative to define additional tracking elements.',
        ['%derivative' => $form_state->getValue('name'), '%entity_type' => $form_state->getValue('entity_type')]));
    }

    $form_state->setRedirect('entity.tether_stats_derivative.collection');

  }

  /**
   * Ajax callback for when the entity_type field is changed.
   *
   * Reloads the list of bundles.
   */
  public static function selectEntityTypeAjaxCallback(array $form, FormStateInterface $form_state) {

    return $form['derivativeBundle'];
  }

  /**
   * Determines if the derivative already exists.
   *
   * @param string $name
   *   The derivative machine name.
   *
   * @return bool
   *   TRUE if the derivative exists, FALSE otherwise.
   */
  public function exists($name) {

    $entity = $this->entityQuery->get('tether_stats_derivative')
      ->condition('name', $name)
      ->execute();

    return !empty($entity);
  }

}
