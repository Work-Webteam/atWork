<?php

namespace Drupal\tether_stats\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\tether_stats\TetherStatsIdentitySet;
use Drupal\tether_stats\TetherStatsIdentitySetInterface;
use Drupal\tether_stats\Entity\TetherStatsDerivative;
use Drupal\tether_stats\Exception\TetherStatsIncompleteIdentitySetException;
use Drupal\tether_stats\Exception\TetherStatsEntityInvalidException;
use Drupal\tether_stats\Exception\TetherStatsDerivativeNotFoundException;
use Drupal\tether_stats\Exception\TetherStatsDerivativeInvalidException;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the TetherStatsIdentitySet methods and validaion.
 *
 * @group tether_stats
 */
class TetherStatsIdentitySetTest extends TetherStatsTestBase {

  /**
   * Simple page node for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testPage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create test page.
    $this->testPage = $this->drupalCreateNode(['type' => 'page', 'title' => "Page", 'url' => $this->getRandomUrl()]);
  }

  /**
   * Test name based sets.
   */
  public function testNameBasedSet() {

    // Test name based identity set.
    $set = new TetherStatsIdentitySet([
      'name' => $this->randomMachineName(12),
    ]);

    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {
      $valid = FALSE;
    }
    $this->assertTrue($valid, SafeMarkup::format('[testIdentitySet]: Name type identity set with name %name passed validation.', ['%name' => $set->get('name')]));

    // Test invalid name.
    $set = new TetherStatsIdentitySet([
      'name' => 'non-machine name!',
    ]);

    $valid = FALSE;
    try {
      $valid = $set->isValid();

    }
    catch (\Exception $e) {
      $this->assertTrue($e instanceof \InvalidArgumentException, SafeMarkup::format('[testIdentitySet]: Invalid element name threw %class.', ['%class' => get_class($e)]));
    }
    $this->assertFalse($valid, '[testIdentitySet]: Invalid element name failed validation.');
  }

  /**
   * Test entity based sets.
   */
  public function testEntityBasedSet() {

    // Test entity bound identity set.
    $set = new TetherStatsIdentitySet([
      'entity_type' => 'node',
      'entity_id' => $this->testPage->id(),
      'url' => $this->testPage->url(),
    ]);

    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {
      $valid = FALSE;
    }
    $this->assertTrue($valid, '[testIdentitySet]: Entity type identity set passed validation.');

    // Test bad entity information.
    $set = new TetherStatsIdentitySet([
      'entity_id' => $this->testPage->id(),
      'entity_type' => 'falseentity',
      'url' => $this->testPage->url(),
    ]);

    $valid = FALSE;
    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {

      $this->assertTrue($e instanceof TetherStatsEntityInvalidException, SafeMarkup::format('[testIdentitySet]: Set with bad entity params threw %class.', ['%class' => get_class($e)]));
    }
    $this->assertFalse($valid, '[testIdentitySet]: Set with bad entity params failed validation.');
  }

  /**
   * Test URL based sets.
   */
  public function testUrlBasedSet() {

    // Test URL based identity set.
    $set = new TetherStatsIdentitySet([
      'url' => $this->getRandomUrl(),
    ]);

    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {
      $valid = FALSE;
    }
    $this->assertTrue($valid, '[testIdentitySet]: Url type identity set passed validation.');
  }

  /**
   * Test incomplete sets.
   */
  public function testIncompleteSet() {

    // Test incompatible and insufficient params.
    $set = new TetherStatsIdentitySet([
      'name' => 'test',
      'entity_type' => 'node',
      'entity_id' => $this->testPage->id(),
    ]);

    $valid = FALSE;
    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {

      $this->assertTrue($e instanceof TetherStatsIncompleteIdentitySetException, SafeMarkup::format('[testIdentitySet]: Set with incompatible params threw %class.', ['%class' => get_class($e)]));
    }
    $this->assertFalse($valid, '[testIdentitySet]: Set with incompatible params failed validation.');

    $set = new TetherStatsIdentitySet([
      'entity_type' => 'node',
      'url' => $this->testPage->url(),
    ]);

    $valid = FALSE;
    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {

      $this->assertTrue($e instanceof TetherStatsIncompleteIdentitySetException, SafeMarkup::format('[testIdentitySet]: Set with insufficient entity params threw %class.', ['%class' => get_class($e)]));
    }
    $this->assertFalse($valid, '[testIdentitySet]: Set with insufficient entity params failed validation.');

    // Test incomplete identity set.
    $set = new TetherStatsIdentitySet([
      'query' => 'a=123',
    ]);

    $valid = FALSE;
    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {

      $this->assertTrue($e instanceof TetherStatsIncompleteIdentitySetException, SafeMarkup::format('[testIdentitySet]: Set with insufficient params threw %class.', ['%class' => get_class($e)]));
    }
    $this->assertFalse($valid, '[testIdentitySet]: Set with insufficient params failed validation.');

  }

  /**
   * Test sets with derivatives.
   */
  public function testSetWithDerivative() {

    // Create a simple derivative which can be applied to any identity set.
    $derivative_entity = TetherStatsDerivative::create(['name' => 'simple', 'derivativeEntityType' => '*', 'derivativeBundle' => '*']);
    $derivative_entity->save();

    // Create a derivative which can be only be applied user entities.
    $derivative_user = TetherStatsDerivative::create(['name' => 'user_only', 'derivativeEntityType' => 'user', 'derivativeBundle' => '*']);
    $derivative_user->save();

    // Create a derivative which can be only be applied node entities of
    // type "page".
    $derivative_article = TetherStatsDerivative::create(['name' => 'page_only', 'derivativeEntityType' => 'node', 'derivativeBundle' => 'page']);
    $derivative_article->save();

    // Create a derivative which can be only be applied node entities of
    // type "article".
    $derivative_article = TetherStatsDerivative::create(['name' => 'article_only', 'derivativeEntityType' => 'node', 'derivativeBundle' => 'article']);
    $derivative_article->save();

    // Test adding the simple derivative to a random identity set.
    $set = $this->getRandomIdentitySet();
    $set->set('derivative', 'simple');

    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {
      $valid = FALSE;
    }
    $this->assertTrue($valid, '[testIdentitySet]: Random set with unconstrained derivative passed validation.');

    // Test adding a false derivative to the random set.
    $set->set('derivative', 'unreal');

    $valid = FALSE;
    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {

      $this->assertTrue($e instanceof TetherStatsDerivativeNotFoundException, SafeMarkup::format('[testIdentitySet]: Set with bad derivative name threw %class.', ['%class' => get_class($e)]));
    }
    $this->assertFalse($valid, '[testIdentitySet]: Set with bad derivative name failed validation.');

    // Create entity bound identity set for the test page node.
    $set = new TetherStatsIdentitySet([
      'entity_type' => 'node',
      'entity_id' => $this->testPage->id(),
      'url' => $this->testPage->url(),
    ]);

    // Test applying derivative for different entity type.
    $set->set('derivative', 'user_only');

    $valid = FALSE;
    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {

      $this->assertTrue($e instanceof TetherStatsDerivativeInvalidException, SafeMarkup::format('[testIdentitySet]: Set with mismatched entity_type derivative threw %class.', ['%class' => get_class($e)]));
    }
    $this->assertFalse($valid, '[testIdentitySet]: Set with mismatched entity_type derivative failed validation.');

    // Test applying derivative for different bundle.
    $set->set('derivative', 'article_only');

    $valid = FALSE;
    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {

      $this->assertTrue($e instanceof TetherStatsDerivativeInvalidException, SafeMarkup::format('[testIdentitySet]: Set with mismatched bundle derivative threw %class.', ['%class' => get_class($e)]));
    }
    $this->assertFalse($valid, '[testIdentitySet]: Set with mismatched bundle derivative failed validation.');

    // Test applying derivative with entity and bundle constraints.
    $set->set('derivative', 'page_only');

    try {
      $valid = $set->isValid();
    }
    catch (\Exception $e) {

      $valid = FALSE;
    }
    $this->assertTrue($valid, '[testIdentitySet]: Set with matching entity and bundle constrained derivative passed validation.');
  }

}
