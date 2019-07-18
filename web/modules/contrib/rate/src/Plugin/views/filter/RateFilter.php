<?php

namespace Drupal\rate\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple filter to handle greater than/less than filters.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("rate_filter")
 */
class RateFilter extends NumericFilter {

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RateFilter constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $id = $this->entityTypeManager->getDefinition($this->tableAlias)
      ->get('entity_keys')['id'];
    $configuration = [
      'table' => 'votingapi_result',
      'field' => 'entity_id',
      'left_table' => $this->tableAlias . '._field_data',
      'left_field' => $this->tableAlias . '.' . $id,
      'operator' => '=',
    ];

    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);

    $this->query->addRelationship('vote', $join, $this->tableAlias . '._field_data');

    $info = $this->operators();

    if (!empty($info[$this->operator]['method'])) {
      $this->query->addWhere('AND', 'vote.entity_type', $this->tableAlias);
      $this->query->addWhere('AND', 'vote.function', 'vote_average');
      $this->{$info[$this->operator]['method']}('vote.value');
    }

  }

}
