<?php

namespace Drupal\Tests\rate\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rate\Traits\AssertRateWidgetTrait;
use Drupal\Tests\rate\Traits\NodeVoteTrait;

/**
 * Tests voting for multilingual content.
 *
 * @group rate
 */
class RateMultilingualTest extends BrowserTestBase {

  use NodeVoteTrait;
  use AssertRateWidgetTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'node',
    'rate',
  ];

  /**
   * The node being used for testing.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add additional language.
    ConfigurableLanguage::createFromLangcode('pl')->save();

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Enable voting on article.
    $this->config('rate.settings')
      ->set('enabled_types_widgets.node', [
        'article' => [
          'widget_type' => 'fivestar',
        ],
      ])
      ->set('use_ajax', FALSE)
      ->save();

    // Creates a translated node.
    $this->node = Node::create([
      'title' => 'English article',
      'type' => 'article',
    ]);
    $this->node->addTranslation('pl', ['title' => 'Polish article']);
    $this->node->save();

    $user = $this->createUser([
      'access content',
      'cast rate vote on node of article',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests for multilingual node.
   */
  public function testMultilingualNode() {
    $session = $this->assertSession();

    // Tests the translation.
    $this->drupalGet('pl/node/' . $this->node->id());
    $this->assertFivestar(0);
    $session->linkExists('Star');
    $session->linkNotExists('Undo');
    $this->voteFivestar(5);
    $this->assertFivestar(5);
    $session->linkNotExists('Star');
    $session->linkExists('Undo');

    $this->drupalGet('node/' . $this->node->id());
    $this->assertFivestar(5);
    $session->linkNotExists('Star');
    $session->linkExists('Undo');
  }

}
