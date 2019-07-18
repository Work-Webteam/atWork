<?php

namespace Drupal\Tests\rate\Functional;

/**
 * Tests for the "Number Up / Down" widget.
 *
 * @group rate
 */
class NodeNumberUpDownWidgetTest extends RateNodeWidgetTestBase {

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
    $this->assertNumberUpDown('+1');
    $session->linkExists('Undo');

    // Tests unvote.
    $this->clickLink('Undo');
    $this->assertNumberUpDown('0');
    $session->linkNotExists('Undo');

    // Vote -1.
    $this->clickLink('Down');
    $this->assertNumberUpDown('-1');
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('node/1');
    $this->assertNumberUpDown('-1');
    $session->linkNotExists('Undo');

    // Vote -1.
    $this->clickLink('Down');
    $this->assertNumberUpDown('-2');
    $session->linkExists('Undo');
  }

}
