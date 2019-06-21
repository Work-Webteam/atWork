<?php

namespace Drupal\Tests\rate\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Rate widget.
 *
 * @group rate
 */
class RateWidgetTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'rate',
  ];

  /**
   * Web users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $webUsers;

  /**
   * An entity to vote on.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $testEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Dummy user 1.
    $this->createUser();

    // Enable voting on test entities.
    $this->config('rate.settings')
      ->set('enabled_types_bundles.entity_test', ['entity_test'])
      ->set('use_ajax', FALSE)
      ->save();
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $display->setComponent('rate_vote_widget', [
      'weight' => 3,
    ]);
    $display->save();

    // A common role for each user. Using a separate role, which is the default,
    // would not provide properly-similar user accounts to test caching.
    $role = $this->createRole([
      'access content',
      'cast rate vote on entity_test of entity_test',
      'view test entity',
    ]);

    foreach (range(1, 2) as $i) {
      $this->webUsers[$i] = $this->createUser();
      $this->webUsers[$i]->addRole($role);
      $this->webUsers[$i]->save();
    }

    $this->testEntity = EntityTest::create(['name' => $this->randomString()]);
    $this->testEntity->save();
  }

  /**
   * Tests widget caching.
   */
  public function testWidgetCaching() {
    // Log in and vote as user 1.
    $this->drupalLogin($this->webUsers[1]);
    $this->drupalGet($this->testEntity->toUrl());

    // Vote on the item.
    $this->clickLink(t('Up'));
    $this->drupalGet($this->testEntity->toUrl());
    $this->assertSession()->pageTextContains('+1');
    $this->assertSession()->linkExists(t('Undo'));

    // Log in as different user, verify widget has a +1 vote, but should still
    // let the user vote on their own.
    $this->drupalLogin($this->webUsers[2]);
    $this->drupalGet($this->testEntity->toUrl());
    $this->assertSession()->pageTextContains('+1');
    $this->assertSession()->linkNotExists(t('Undo'));
  }

}
