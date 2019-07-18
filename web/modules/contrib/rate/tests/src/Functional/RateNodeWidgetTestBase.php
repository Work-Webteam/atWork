<?php

namespace Drupal\Tests\rate\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rate\Traits\AssertRateWidgetTrait;

/**
 * Base class for Rate functional tests.
 *
 * @package Drupal\Tests\rate\Functional
 */
abstract class RateNodeWidgetTestBase extends BrowserTestBase {

  use AssertRateWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'rate',
  ];

  /**
   * The widget type for testing.
   *
   * @var string
   */
  protected $widget;

  /**
   * An array of link labels, e.g. ['Up', 'Down'].
   *
   * @var array
   */
  protected $labels;

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

    // Enable voting on article.
    $this->config('rate.settings')
      ->set('enabled_types_widgets.node', [
        'article' => [
          'widget_type' => $this->widget,
        ],
      ])
      ->set('use_ajax', FALSE)
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
    $this->users[2] = $this->createUser($permissions);
  }

  /**
   * Tests voting permissions.
   */
  public function testPermissions() {
    $this->drupalLogin($this->users[0]);
    $this->drupalGet('node/1');
    foreach ($this->labels as $label) {
      $this->assertSession()->linkExists($label);
    }

    // Create a user without voting permissions.
    $user = $this->createUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('node/1');
    foreach (array_merge($this->labels, ['Undo']) as $label) {
      $this->assertSession()->linkNotExists($label);
    }
  }

}
