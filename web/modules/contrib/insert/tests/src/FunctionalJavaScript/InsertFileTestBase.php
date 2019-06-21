<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\TestFileCreationTrait;

abstract class InsertFileTestBase extends JavascriptTestBase {

  use FileFieldCreationTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }
  use TextFieldCreationTrait;

  /**
   * @var array
   */
  public static $modules = array('node', 'file', 'insert');

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    if ($this->profile != 'standard') {
      $this->createContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->createContentType(array('type' => 'article', 'name' => 'Article'));
    }

    $this->adminUser = $this->createUser(array(), NULL, TRUE);

    $this->drupalLogin($this->adminUser);
  }

}
