<?php

namespace Drupal\Tests\rate\FunctionalJavascript;

/**
 * Tests for the "Number Up / Down" widget.
 *
 * @group rate
 */
class NodeNumberUpDownWidgetTest extends RateJavascriptNodeWidgetTestBase {

  /**
   * {@inheritdoc}
   */
  protected $widget = 'number_up_down';

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
    $this->assertNumberUpDown('0');

    // Vote +1.
    $this->clickLink('Up');
    $session->assertWaitOnAjaxRequest();
    $this->assertNumberUpDown('+1');
    $session->linkExists('Undo');

    // Tests unvote.
    $this->clickLink('Undo');
    $session->assertWaitOnAjaxRequest();
    $this->assertNumberUpDown('0');
    $session->linkNotExists('Undo');

    // Vote -1.
    $this->clickLink('Down');
    $session->assertWaitOnAjaxRequest();
    $this->assertNumberUpDown('-1');
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('node/1');
    $this->assertNumberUpDown('-1');
    $session->linkNotExists('Undo');

    // Vote -1.
    $this->clickLink('Down');
    $session->assertWaitOnAjaxRequest();
    $this->assertNumberUpDown('-2');
    $session->linkExists('Undo');
  }

}
