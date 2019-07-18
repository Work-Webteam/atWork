<?php

namespace Drupal\Tests\rate\Functional;

use Drupal\Tests\rate\Traits\NodeVoteTrait;

/**
 * Tests for the "Fivestar" widget.
 *
 * @group rate
 */
class NodeFivestarWidgetTest extends RateNodeWidgetTestBase {

  use NodeVoteTrait;

  /**
   * {@inheritdoc}
   */
  protected $widget = 'fivestar';

  /**
   * {@inheritdoc}
   */
  protected $labels = ['Star'];

  /**
   * Tests voting.
   */
  public function testVoting() {
    $session = $this->assertSession();

    // Log in as first user.
    $this->drupalLogin($this->users[0]);
    $this->drupalGet('node/1');
    $this->assertFivestar(0);
    $session->linkExists('Star');
    $session->linkNotExists('Undo');

    // Vote 5 stars.
    $this->voteFivestar(5);
    $this->assertFivestar(5);
    $session->linkExists('Undo');

    // Log in as different user.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('node/1');
    $this->assertFivestar(5);
    $session->linkNotExists('Undo');

    // Vote 3 stars.
    $this->voteFivestar(3);
    $this->assertFivestar(4);
    $session->linkExists('Undo');

    // Tests unvote.
    $this->clickLink('Undo');
    $this->assertFivestar(5);
    $session->linkNotExists('Undo');
  }

}
