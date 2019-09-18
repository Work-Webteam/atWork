<?php

namespace Drupal\Tests\focal_point\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Focal Point widget works properly.
 *
 * @group focal_point
 */
class FocalPointWidgetTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'focal_point'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();


  }

  /**
   * Tests that the reaction rule listing page works.
   */
  public function testFocalPointWidget() {
    $account = $this->drupalCreateUser(['administer nodes']);
    $this->drupalLogin($account);

    $this->drupalGet('admin');
    $this->assertSession()->statusCodeEquals(200);

    // Test that there is an empty reaction rule listing.
//    $this->assertSession()->pageTextContains('There is no Reaction Rule yet.');
  }
}