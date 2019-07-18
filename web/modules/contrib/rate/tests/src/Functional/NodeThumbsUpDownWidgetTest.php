<?php

namespace Drupal\Tests\rate\Functional;

/**
 * Tests for the "Thumbs Up / Down" widget.
 *
 * @group rate
 */
class NodeThumbsUpDownWidgetTest extends RateNodeWidgetTestBase {

  /**
   * {@inheritdoc}
   */
  protected $widget = 'thumbs_up_down';

  /**
   * {@inheritdoc}
   */
  protected $labels = ['Up', 'Down'];

  /**
   * Tests voting.
   */
  public function testVoting() {
    $session = $this->assertSession();

    // Log in as first user.
    $this->drupalLogin($this->users[0]);
    $this->drupalGet('node/1');
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
    $this->drupalGet('node/1');
    $this->assertThumbsUpDown(100, 0);
    $session->linkNotExists('Undo');

    // Vote 'Down'.
    $this->clickLink('Down');
    $this->assertThumbsUpDown(50, 50);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[2]);
    $this->drupalGet('node/1');
    $this->assertThumbsUpDown(50, 50);
    $session->linkNotExists('Undo');

    // Vote 'Up'.
    $this->clickLink('Up');
    $this->assertThumbsUpDown(67, 33);
    $session->linkExists('Undo');
  }

}
