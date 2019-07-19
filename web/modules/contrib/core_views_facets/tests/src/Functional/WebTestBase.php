<?php

namespace Drupal\Tests\core_views_facets\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\TestHelperTrait;
use Drupal\facets\Entity\Facet;

/**
 * Provides the base class for web tests for Search API.
 */
abstract class WebTestBase extends BrowserTestBase {

  use TestHelperTrait;
  use BlockTestTrait {
    createFacet as traitCreateFacet;
  }

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'views',
    'node',
    'field',
    'facets',
    'block',
    'taxonomy',
    'core_views_facets',
    'user',
    'core_views_facets_test_views',
  ];

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user without Search / Facet admin permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unauthorizedUser;

  /**
   * The anonymous user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymousUser;

  /**
   * A search index ID.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

  /**
   * The generated test entities, keyed by ID.
   *
   * @var \Drupal\entity_test\Entity\EntityTestMulRevChanged[]
   */
  protected $entities = [];

  /**
   * The exposed filters facet source ID.
   *
   * @var string
   */
  protected $exposedFiltersFacetSourceId;

  /**
   * The contextual filters facet source ID.
   *
   * @var string
   */
  protected $contextualFiltersFacetSourceId;

  /**
   * The CoreViewIntegrationTest view path.
   *
   * @var string
   */
  protected $facetUrl;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the users used for the tests.
    $this->adminUser = $this->drupalCreateUser([
      'administer facets',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
    ]);

    $this->unauthorizedUser = $this->drupalCreateUser(['access administration pages']);
    $this->anonymousUser = $this->drupalCreateUser();
    $this->exposedFiltersFacetSourceId = 'core_views_exposed_filter:core_views_facets_basic_integration__page_1';
    $this->contextualFiltersFacetSourceId = 'core_views_contextual_filter:core_views_facets_basic_integration__page_1';
    $this->facetUrl = 'core-views-facets-basic-integration';
  }

  /**
   * Sets up the necessary bundles on the test entity type.
   */
  protected function setUpExampleStructure() {
    entity_test_create_bundle('item');
    entity_test_create_bundle('article');
  }

  /**
   * Creates several test entities.
   */
  protected function insertExampleContent() {
    $count = \Drupal::entityQuery('entity_test')
      ->count()
      ->execute();

    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test');
    $this->entities[1] = $entity_test_storage->create([
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange'],
      'category' => 'item_category',
    ]);
    $this->entities[1]->save();
    $this->entities[2] = $entity_test_storage->create([
      'name' => 'foo test',
      'body' => 'bar test',
      'type' => 'item',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ]);
    $this->entities[2]->save();
    $this->entities[3] = $entity_test_storage->create([
      'name' => 'bar',
      'body' => 'test foobar',
      'type' => 'item',
    ]);
    $this->entities[3]->save();
    $this->entities[4] = $entity_test_storage->create([
      'name' => 'foo baz',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => ['apple', 'strawberry', 'grape'],
      'category' => 'article_category',
    ]);
    $this->entities[4]->save();
    $this->entities[5] = $entity_test_storage->create([
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => ['orange', 'strawberry', 'grape', 'banana'],
      'category' => 'article_category',
    ]);
    $this->entities[5]->save();
    $count = \Drupal::entityQuery('entity_test')
      ->count()
      ->execute() - $count;
    $this->assertEquals($count, 5, "$count items inserted.");
  }

  /**
   * Add a facet trough the UI.
   *
   * @param string $name
   *   The facet name.
   * @param string $id
   *   The facet id.
   * @param string $field
   *   The facet field.
   * @param string $display_id
   *   The display id.
   * @param string $source
   *   Facet source.
   * @param string $source_type
   *   Either exposed or contextual.
   * @param bool $allowBlockCreation
   *   Automatically create a block.
   */
  protected function createFacet($name, $id, $field = 'type', $display_id = 'page_1', $source = 'core_views_facets_basic_integration', $source_type = 'exposed', $allowBlockCreation = TRUE) {
    switch ($source_type) {
      case 'contextual':
        list($facet_source_id) = explode(':', $this->contextualFiltersFacetSourceId);
        $source_id = str_replace(':', '__', $this->contextualFiltersFacetSourceId);
        break;

      case 'exposed':
      default:
        list($facet_source_id) = explode(':', $this->exposedFiltersFacetSourceId);
        $source_id = str_replace(':', '__', $this->exposedFiltersFacetSourceId);
        break;
    }

    // We didn't have a facet source config entity yet for this facet source
    // plugin, so we create it on the fly.
    $storage = \Drupal::entityTypeManager()->getStorage('facets_facet_source');
    $storage->create([
      'id' => $source_id,
      'name' => str_replace('__', ':', $source_id),
      'filter_key' => '',
      'url_processor' => 'core_views_url_processor',
    ])->save();
    $facet_source = "{$facet_source_id}:{$source}__{$display_id}";

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = Facet::create([
      'id' => $id,
      'name' => $name,
      'weight' => 0,
    ]);
    $facet->setFacetSourceId($facet_source);
    $facet->setFieldIdentifier($field);
    $facet->setUrlAlias($id);
    $facet->setWidget('links', ['show_numbers' => TRUE]);
    $facet->addProcessor([
      'processor_id' => 'url_processor_handler',
      'weights' => ['pre_query' => -10, 'build' => -10],
      'settings' => [],
    ]);
    $facet->setEmptyBehavior(['behavior' => 'none']);
    $facet->setOnlyVisibleWhenFacetSourceIsVisible(TRUE);
    $facet->save();

    if ($allowBlockCreation) {
      $this->blocks[$id] = $this->createBlock($id);
    }
  }

}
