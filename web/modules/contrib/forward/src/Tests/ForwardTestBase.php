<?php

namespace Drupal\forward\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides a base class for testing the Forward module.
 */
abstract class ForwardTestBase extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['forward', 'node', 'user', 'forward_test'];

  /**
   * A simple user with 'access content' permission.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * A user with 'access content' and 'access forward' permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $forwardUser;

  /**
   * An user with permissions to administer Mollom.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Create test users.
    $this->webUser = $this->drupalCreateUser(['access content']);
    $this->forwardUser = $this->drupalCreateUser(['access content', 'access forward']);

    $permissions = [
      'access forward',
      'administer forward',
      'administer users',
      'bypass node access',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

}
