<?php

namespace Drupal\Tests\rate\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\rate\Traits\NodeVoteTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests Views Fivestar Widget.
 *
 * @group rate
 */
class ViewsFilterTest extends ViewTestBase {

  use NodeVoteTrait;

  /**
   * Problem with views rate widget schema.
   *
   * @var bool
   *
   * @todo Remove when we fix https://www.drupal.org/node/2879568
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_views_filter'];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'views',
    'rate',
    'rate_views_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(get_class($this), ['rate_views_test']);

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Enable 'Fivestar' on Article.
    $this->config('rate.settings')
      ->set('enabled_types_widgets.node', [
        'article' => [
          'widget_type' => 'fivestar',
        ],
      ])
      ->set('use_ajax', FALSE)
      ->save();

    foreach (range(1, 6) as $id) {
      $this->drupalCreateNode([
        'type' => 'article',
        'title' => 'Article ' . $id,
        'nid' => $id,
      ])->save();
    }

    $user = $this->createUser(['access content', 'cast rate vote on node of article']);
    $this->drupalLogin($user);
  }

  /**
   * Tests a views filter.
   */
  public function testFilterMin() {
    $session = $this->assertSession();

    $this->drupalGet('node/1');
    $this->voteFivestar(1);

    $this->drupalGet('node/2');
    $this->voteFivestar(2);

    $this->drupalGet('node/3');
    $this->voteFivestar(3);

    $this->drupalGet('node/4');
    $this->voteFivestar(4);

    $this->drupalGet('node/5');
    $this->voteFivestar(5);

    $this->drupalGet('test_views_filter');
    $session->pageTextContains('Article 1');
    $session->pageTextContains('Article 2');
    $session->pageTextContains('Article 3');
    $session->pageTextContains('Article 4');
    $session->pageTextContains('Article 5');
    $session->pageTextContains('Article 6');

    // Tests filter minimum 1 star.
    $this->drupalGet('test_views_filter', ['query' => ['node_rate_field' => 1]]);
    $session->pageTextNotContains('Article 1');
    $session->pageTextContains('Article 2');
    $session->pageTextContains('Article 3');
    $session->pageTextContains('Article 4');
    $session->pageTextContains('Article 5');
    $session->pageTextNotContains('Article 6');

    // Tests filter minimum 3 stars.
    $this->drupalGet('test_views_filter', ['query' => ['node_rate_field' => 3]]);
    $session->pageTextNotContains('Article 1');
    $session->pageTextNotContains('Article 2');
    $session->pageTextNotContains('Article 3');
    $session->pageTextContains('Article 4');
    $session->pageTextContains('Article 5');
    $session->pageTextNotContains('Article 6');
  }

}
