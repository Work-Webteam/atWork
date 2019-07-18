<?php

namespace Drupal\Tests\rate\Functional;

/**
 * Tests for the "Yes / No" widget.
 *
 * @group rate
 */
class NodeYesNoWidgetTest extends RateNodeWidgetTestBase {

  /**
   * {@inheritdoc}
   */
  protected $widget = 'yesno';

  /**
   * {@inheritdoc}
   */
  protected $labels = ['Yes', 'No'];

  /**
   * Tests voting.
   */
  public function testVoting() {
    $session = $this->assertSession();

    // Log in as first user.
    $this->drupalLogin($this->users[0]);
    $this->drupalGet('node/1');
    $this->assertYesNo(0, 0);

    // Vote 'Yes'.
    $this->clickLink('Yes');
    $this->assertYesNo(1, 0);
    $session->linkExists('Undo');

    // Unvote 'Yes'.
    $this->clickLink('Undo');
    $this->assertYesNo(0, 0);
    $session->linkNotExists('Undo');

    // Vote 'Yes'.
    $this->clickLink('Yes');
    $this->assertYesNo(1, 0);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('node/1');
    $this->assertYesNo(1, 0);
    $session->linkNotExists('Undo');

    // Vote 'No'.
    $this->clickLink('No');
    $this->assertYesNo(1, 1);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[2]);
    $this->drupalGet('node/1');
    $this->assertYesNo(1, 1);
    $session->linkNotExists('Undo');

    // Vote 'Yes'.
    $this->clickLink('Yes');
    $this->assertYesNo(2, 1);
    $session->linkExists('Undo');
  }

}
