<?php

namespace Drupal\Tests\rate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rate\Traits\AssertRateWidgetTrait;
use Drupal\Tests\rate\Traits\NodeVoteTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests Views Fivestar Widget.
 *
 * @group rate
 */
class ViewsFivestarWidgetTest extends WebDriverTestBase {

  use NodeVoteTrait;
  use AssertRateWidgetTrait;

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
  public static $testViews = ['test_views_widget'];

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
   * An array of users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $users;

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
      ->set('use_ajax', TRUE)
      ->save();

    $this->drupalCreateNode([
      'type' => 'article',
      'nid' => 1,
    ])->save();

    $this->drupalCreateNode([
      'type' => 'article',
      'nid' => 2,
    ])->save();

    $permissions = [
      'access content',
      'cast rate vote on node of article',
    ];
    $this->users[0] = $this->createUser($permissions);
    $this->users[1] = $this->createUser($permissions);
  }

  /**
   * Tests a fivestar views widget.
   */
  public function testFivestarViewsWidget() {
    $session = $this->assertSession();

    $this->drupalLogin($this->users[0]);
    $this->drupalGet('test_views_widget');

    // Vote 1 star on the first article.
    $this->assertFivestarById(1, 0);
    $this->voteFivestarById(1, 1);
    $session->assertWaitOnAjaxRequest();
    $this->assertFivestarById(1, 1);
    $session->linkExists('Undo');

    // Vote 3 stars on the second article.
    $this->assertFivestarById(2, 0);
    $this->voteFivestarById(2, 3);
    $session->assertWaitOnAjaxRequest();
    $this->assertFivestarById(2, 3);
    $session->linkExists('Undo');

    $this->drupalLogin($this->users[1]);
    $this->drupalGet('test_views_widget');

    // Vote 5 stars on the first article.
    $this->voteFivestarById(1, 5);
    $session->assertWaitOnAjaxRequest();
    $this->assertFivestarById(1, 3);
    $session->linkExists('Undo');

    // Vote 5 stars on the second article.
    $this->voteFivestarById(2, 4);
    $session->assertWaitOnAjaxRequest();
    $this->assertFivestarById(2, 3);
    $session->linkExists('Undo');

    // Unvote first article.
    $this->unVoteFivestarById(1);
    $session->assertWaitOnAjaxRequest();
    $this->assertFivestarById(1, 1);
    $session->linkExists('Undo');

    // Unvote second article.
    $this->unVoteFivestarById(2);
    $session->assertWaitOnAjaxRequest();
    $this->assertFivestarById(2, 3);
    $session->linkNotExists('Undo');
  }

}
