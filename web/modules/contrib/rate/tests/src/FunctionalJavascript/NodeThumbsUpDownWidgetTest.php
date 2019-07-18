<?php

namespace Drupal\Tests\rate\FunctionalJavascript;

/**
 * Tests for the "Thumbs Up / Down" widget.
 *
 * @group rate
 */
class NodeThumbsUpDownWidgetTest extends RateJavascriptNodeWidgetTestBase {

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
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUpDown(100, 0);
    $session->linkExists('Undo');

    // Unvote 'Up'.
    $this->clickLink('Undo');
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUpDown(0, 0);
    $session->linkNotExists('Undo');

    // Vote 'Up'.
    $this->clickLink('Up');
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUpDown(100, 0);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('node/1');
    $this->assertThumbsUpDown(100, 0);
    $session->linkNotExists('Undo');

    // Vote 'Down'.
    $this->clickLink('Down');
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUpDown(50, 50);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[2]);
    $this->drupalGet('node/1');
    $this->assertThumbsUpDown(50, 50);
    $session->linkNotExists('Undo');

    // Vote 'Up'.
    $this->clickLink('Up');
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUpDown(67, 33);
    $session->linkExists('Undo');
  }

}
