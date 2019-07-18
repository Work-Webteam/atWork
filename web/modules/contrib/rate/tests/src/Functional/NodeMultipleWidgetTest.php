<?php

namespace Drupal\Tests\rate\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rate\Traits\AssertRateWidgetTrait;
use Drupal\Tests\rate\Traits\NodeVoteTrait;

/**
 * Tests of multiple widgets for different nodes.
 *
 * @group rate
 */
class NodeMultipleWidgetTest extends BrowserTestBase {

  use AssertRateWidgetTrait;
  use NodeVoteTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'rate',
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
  protected function setUp() {
    parent::setUp();

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    NodeType::create([
      'type' => 'page',
      'name' => 'Basic Page',
    ])->save();

    // Enable 'Fivestar' on Article and 'Thumbs Up / Down' on Basic Page.
    $this->config('rate.settings')
      ->set('enabled_types_widgets.node', [
        'article' => [
          'widget_type' => 'fivestar',
        ],
        'page' => [
          'widget_type' => 'thumbs_up_down',
        ],
      ])
      ->set('use_ajax', FALSE)
      ->save();

    $this->drupalCreateNode([
      'type' => 'article',
      'nid' => 1,
    ])->save();

    $this->drupalCreateNode([
      'type' => 'page',
      'nid' => 2,
    ])->save();

    $permissions = [
      'access content',
      'cast rate vote on node of article',
      'cast rate vote on node of page',
    ];
    $this->users[0] = $this->createUser($permissions);
    $this->users[1] = $this->createUser($permissions);
    $this->users[2] = $this->createUser($permissions);
  }

  /**
   * Tests voting.
   */
  public function testVoting() {
    $session = $this->assertSession();

    // Tests 'Fivestar' voting on Article.
    $this->drupalLogin($this->users[0]);
    $this->drupalGet('node/1');
    $this->assertFivestar(0);
    $session->linkNotExists('Undo');

    // Vote 5 stars.
    $this->voteFivestar(5);
    $this->assertFivestar(5);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('node/1');
    $this->assertFivestar(5);
    $session->linkNotExists('Undo');

    // Vote 3 stars.
    $this->voteFivestar(3);
    $this->assertFivestar(4);
    $session->linkExists('Undo');

    // Tests unvote.
    $this->clickLink('Undo');
    $this->assertFivestar(5);
    $session->linkNotExists('Undo');

    // Tests 'Thumbs Up / Down' voting on Basic Page.
    $this->drupalLogin($this->users[0]);
    $this->drupalGet('node/2');
    $this->assertThumbsUpDown(0, 0);

    // Vote 'Up'.
    $this->clickLink('Up');
    $this->assertThumbsUpDown(100, 0);
    $session->linkExists('Undo');

    // Unvote 'Up'.
    $this->clickLink('Undo');
    $this->assertThumbsUpDown(0, 0);
    $session->linkNotExists('Undo');

    // Vote 'Up'.
    $this->clickLink('Up');
    $this->assertThumbsUpDown(100, 0);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('node/2');
    $this->assertThumbsUpDown(100, 0);
    $session->linkNotExists('Undo');

    // Vote 'Down'.
    $this->clickLink('Down');
    $this->assertThumbsUpDown(50, 50);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[2]);
    $this->drupalGet('node/2');
    $this->assertThumbsUpDown(50, 50);
    $session->linkNotExists('Undo');

    // Vote 'Up'.
    $this->clickLink('Up');
    $this->assertThumbsUpDown(67, 33);
    $session->linkExists('Undo');
  }

}
