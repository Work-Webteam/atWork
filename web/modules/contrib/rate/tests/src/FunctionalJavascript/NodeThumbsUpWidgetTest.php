<?php

namespace Drupal\Tests\rate\FunctionalJavascript;

/**
 * Tests for the "Thumbs Up" widget.
 *
 * @group rate
 */
class NodeThumbsUpWidgetTest extends RateJavascriptNodeWidgetTestBase {

  /**
   * {@inheritdoc}
   */
  protected $widget = 'thumbs_up';

  /**
   * {@inheritdoc}
   */
  protected $labels = ['Up'];

  /**
   * Tests voting.
   */
  public function testVoting() {
    $session = $this->assertSession();

    // Log in as first user.
    $this->drupalLogin($this->users[0]);
    $this->drupalGet('node/1');
    $this->assertThumbsUp(0);
    $session->linkNotExists('Undo');

    // Vote 'Up'.
    $this->clickLink('Up');
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUp(1);
    $session->linkExists('Undo');

    // Unvote 'Up'.
    $this->clickLink('Undo');
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUp(0);
    $session->linkNotExists('Undo');

    // Vote 'Up'.
    $this->clickLink('Up');
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUp(1);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('node/1');
    $this->assertThumbsUp(1);
    $session->linkNotExists('Undo');

    // Vote 'Up'.
    $this->clickLink('Up');
    $session->assertWaitOnAjaxRequest();
    $this->assertThumbsUp(2);
    $session->linkExists('Undo');
  }

}
