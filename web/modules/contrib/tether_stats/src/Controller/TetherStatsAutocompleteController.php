<?php

namespace Drupal\tether_stats\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides an autocomplete callback for derivative names.
 */
class TetherStatsAutocompleteController implements ContainerInjectionInterface {

  /**
   * Derivative entity query interface.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $derivativeEntityQuery;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new TetherStatsAutocompleteController object.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $derivative_entity_query
   *   The entity query service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(QueryInterface $derivative_entity_query, EntityManagerInterface $entity_manager) {

    $this->derivativeEntityQuery = $derivative_entity_query;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity.query')->get('tether_stats_derivative'),
        $container->get('entity.manager')
    );
  }

  /**
   * Retrieves suggestions for derivative autocompletion.
   *
   * This function outputs derivative name suggestions in response to Ajax
   * requests made by the element finder form. The output is a JSON object of
   * plain-text derivative name suggestions, keyed by the user-entered value.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   *   When valid derivative name is specified, a JSON response containing the
   *   autocomplete suggestions for names. Otherwise a normal response
   *   containing an error message.
   */
  public function derivativeAutocomplete(Request $request) {

    $name_typed = $request->query->get('q');

    $matches = [];

    // Select rows that match by derivative name.
    $names = $this->derivativeEntityQuery
      ->condition('name', $name_typed, 'CONTAINS')
      ->range(0, 10)
      ->execute();

    if (!empty($names)) {

      foreach ($names as $name) {

        $matches[] = ['value' => $name, 'label' => $name];
      }
    }

    return new JsonResponse($matches);
  }

}
