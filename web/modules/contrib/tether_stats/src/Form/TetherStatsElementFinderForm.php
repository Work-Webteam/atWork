<?php

namespace Drupal\tether_stats\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tether_stats\TetherStatsManagerInterface;
use Drupal\tether_stats\TetherStatsIdentitySet;
use Drupal\tether_stats\Exception\TetherStatsDerivativeNotFoundException;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\tether_stats\Exception\TetherStatsDerivativeInvalidException;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\user\PrivateTempStore;

/**
 * Form to lookup stats elements created by Tether Stats.
 */
class TetherStatsElementFinderForm extends FormBase {

  /**
   * The Tether Stats manager service.
   *
   * @var TetherStatsManagerInterface
   */
  protected $manager;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Storage of private temporary data for the current user.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Form constructor.
   *
   * @param TetherStatsManagerInterface $manager
   *   The Tether Stats manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The EntityQuery object for derivatives.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link manager service.
   * @param \Drupal\user\PrivateTempStore $temp_store
   *   The private temporary storage for tether_stats.
   */
  public function __construct(TetherStatsManagerInterface $manager, QueryFactory $query_factory, EntityManagerInterface $entity_manager, LinkGeneratorInterface $link_generator, PrivateTempStore $temp_store) {

    $this->manager = $manager;
    $this->entityQueryFactory = $query_factory;
    $this->entityManager = $entity_manager;
    $this->linkGenerator = $link_generator;
    $this->privateTempStore = $temp_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('tether_stats.manager'),
      $container->get('entity.query'),
      $container->get('entity.manager'),
      $container->get('link_generator'),
      $container->get('user.private_tempstore')->get('tether_stats')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'tether_stats_element_finder';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];

    $form['element_finder'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element Finder'),
      '#description' => $this->t('Use this form to find the element id matching an identifying set. Then you may review the statistics information tracked for that element.'),
    ];

    $form['element_finder']['identity_type'] = [
      '#type' => 'radios',
      '#options' => [
        'find_by_entity' => $this->t('Find by entity'),
        'find_by_url' => $this->t('Find non-entity page by url'),
        'find_by_name' => $this->t('Find by name'),
      ],
      '#default_value' => $form_state->getValue('identity_type', 'find_by_entity'),
    ];

    $form['element_finder']['find_by_entity'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Find by Entity'),
      '#states' => [
        'visible' => [
          ':input[name="identity_type"]' => ['value' => 'find_by_entity'],
        ],
      ],
    ];

    $options = [];

    // Extract only the content entities.
    $entity_types = $this->entityManager->getEntityTypeLabels(TRUE);
    $options += reset($entity_types);

    $form['element_finder']['find_by_entity']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t('The entity_type for which the following entity_id applies.'),
      '#options' => $options,
      '#default_value' => $form_state->getValue('entity_type', 'node'),
    ];

    $form['element_finder']['find_by_entity']['entity_id'] = [
      '#type' => 'textfield',
      '#title' => 'Entity Id',
      '#description' => $this->t('The key for the entity of the specified entity type. This could be the nid, or uid etc depending on the context.'),
    ];

    $form['element_finder']['find_by_name'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Find by Name'),
      '#states' => [
        'visible' => [
          ':input[name="identity_type"]' => ['value' => 'find_by_name'],
        ],
      ],
    ];

    $form['element_finder']['find_by_name']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('A unique string identifier that maps directly to an element. No other identity fields are required when an element is identified this way.'),
    ];

    $form['element_finder']['find_by_url'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Find Non-entity Page by Url'),
      '#states' => [
        'visible' => [
          ':input[name="identity_type"]' => ['value' => 'find_by_url'],
        ],
      ],
    ];

    $form['element_finder']['find_by_url']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('The URL of the page to which an element is mapped to. The URL should include a preceding slash.'),
    ];

    $form['element_finder']['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query String'),
      '#description' => $this->t('The query string applied to the page.'),
    ];

    $options = [0 => '- ' . $this->t('none') . ' -'];

    $entity_type = ($form_state->has('entity_type') && $form_state->get('find_by_entity') == 'find_by_entity') ? $form_state->get('entity_type') : NULL;

    $derivatives = $this->getDerivatives($entity_type);
    $options += array_combine($derivatives, $derivatives);

    $form['element_finder']['derivative'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Derivative'),
      '#description' => $this->t('The derivative name if you wish to find an element derived from another.'),
      '#autocomplete_route_name' => 'tether_stats.derivative.autocomplete',
    ];

    $form['element_finder']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Element'),
    ];

    $form['stats_element'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element'),
      '#prefix' => '<div id="stats_element-div">',
      '#suffix' => '</div>',
    ];

    $found_element = $this->privateTempStore->get('found_element');

    if (isset($found_element)) {

      $element_url = Url::fromRoute('tether_stats.overview.element', ['elid' => $found_element->getId()]);
      $link = $this->linkGenerator->generate($this->t('Element Overview Page'), $element_url);

      $form['stats_element']['elid'] = [
        '#type' => 'item',
        '#title' => $this->t('Element Id'),
        '#markup' => '<p>' . $found_element->getId() . " - {$link}</p>",
      ];
    }
    else {

      $form['stats_element']['elid'] = [
        '#type' => 'item',
        '#markup' => '<p>' . $this->t('Use the finder tool above find an element.') . '</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $identity_params = [];

    switch ($form_state->getValue('identity_type')) {

      case 'find_by_entity':
        $entity_id = $form_state->getValue('entity_id');

        if ($entity_id) {

          if (is_numeric($entity_id)) {

            $entity = entity_load($form_state->getValue('entity_type'), $entity_id);

            if (isset($entity)) {

              $identity_params['entity_type'] = $form_state->getValue('entity_type');
              $identity_params['entity_id'] = $form_state->getValue('entity_id');
            }
            else {

              $form_state->setError($form['element_finder']['find_by_entity']['entity_id'], $this->t('An entity could not be found for that <em>Entity Id</em>.'));
            }
          }
          else {

            $form_state->setError($form['element_finder']['find_by_entity']['entity_id'], $this->t('The <em>Entity Id</em> must be numeric.'));
          }
        }
        else {

          $form_state->setError($form['element_finder']['find_by_entity']['entity_id'], $this->t('The <em>Entity Id</em> field is required.'));
        }
        break;

      case 'find_by_name':
        if ($form_state->getValue('name')) {

          $identity_params['name'] = $form_state->getValue('name');
        }
        else {

          $form_state->setError($form['element_finder']['find_by_name']['name'], $this->t('A <em>Name</em> identifying the element is required.'));
        }
        break;

      case 'find_by_url':
        $path = $form_state->getValue('url');

        if ($path) {

          if (strpos($path, '?') !== FALSE) {

            $form_state->setError($form['element_finder']['find_by_url']['url'], $this->t('The specified path must not contain a query string. Use the Query field.'));
          }
          else {

            $url = \Drupal::pathValidator()->getUrlIfValidWithoutAccessCheck($path);

            if ($url !== FALSE) {

              $identity_params['url'] = $path;
            }
            else {

              $form_state->setError($form['element_finder']['find_by_url']['url'], $this->t('The specified path is invalid or not recognized.'));
            }
          }
        }
        else {

          $form_state->setError($form['element_finder']['find_by_url']['url'], $this->t('The <em>Url</em> field is required.'));
        }
        break;

    }

    if (!empty($identity_params)) {

      if ($form_state->hasValue('derivative') && $derivative = $form_state->getValue('derivative')) {

        $identity_params['derivative'] = $derivative;
      }

      $identity_set = new TetherStatsIdentitySet($identity_params);
      $identity_set->setIgnoreDisabledDerivativeOnValidate();

      try {

        $identity_set->isValid();
      }
      catch (TetherStatsDerivativeNotFoundException $e) {

        $form_state->setError($form['element_finder']['derivative'], $this->t('No derivative by the name %derivative exists.', ['%derivative' => $identity_params['derivative']]));
      }
      catch (TetherStatsDerivativeInvalidException $e) {

        // The exception occurs when the derivative constraints prevent it from
        // being applied to this identity set.
        $form_state->setError($form['element_finder']['derivative'], $this->t('The derivative %derivative has constraints which forbid it from being applied to elements of this type. No element will match this query.', ['%derivative' => $identity_params['derivative']]));
      }
      catch (\Exception $e) {

        // With the earlier validation we should not get any other exceptions
        // thrown here unless the entity configuration has changed since the
        // form was loaded.
        $form_state->setError($form['element_finder']['identity_type'], $this->t('Unable to validate identity set. This form may be out of date.'));
      }

      $form_state->set('identity_set', $identity_set);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $identity_set = $form_state->get('identity_set');

    $element = $this->manager->getStorage()->loadElementFromIdentitySet($identity_set);

    if (isset($element)) {

      $this->privateTempStore->set('found_element', $element);
      $this->messenger()->addMessage($this->t('The element id %elid has been found and added to the %fieldset fieldset below.',
        [
          '%elid' => $element->getId(),
          '%fieldset' => 'Element',
        ]));
    }
    else {

      $this->privateTempStore->delete('found_element');
      $this->messenger()->addWarning($this->t('No matching element found.'));
    }
  }

  /**
   * Gets a Query object for the TetherStatsDerivative entity.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The Query object.
   */
  private function getDerivativeQuery() {

    return $this->entityQueryFactory->get('tether_stats_derivative');
  }

  /**
   * Gets a list of all derivative names that can be applied.
   *
   * Given the $entity_type and $bundle constraints, the method will return
   * a list of derivative names which can be applied.
   *
   * @param string|null $entity_type
   *   The entity type. If null, the derivative must not be entity specific.
   * @param string|null $bundle
   *   The bundle type. If null, the derivative must not be bundle specific.
   *
   * @return array
   *   A list of all derivative names that can be applied.
   */
  private function getDerivatives($entity_type = NULL, $bundle = NULL) {

    $query = $this->getDerivativeQuery();

    if (isset($entity_type)) {

      $query->condition(db_or()->condition('derivativeEntityType', '*')->condition('derivativeEntityType', $entity_type));
    }
    else {

      $query->condition('derivativeEntityType', '*');
    }

    if (isset($bundle)) {

      $query->condition(db_or()->condition('derivativeBundle', '*')->condition('derivativeBundle', $bundle));
    }
    else {

      $query->condition('derivativeBundle', '*');
    }

    return $query->execute();
  }

}
