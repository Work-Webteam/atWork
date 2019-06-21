<?php

namespace Drupal\forward\Tests;

/**
 * Test the Forward form.
 *
 * @group forward
 */
class ForwardFormTest extends ForwardTestBase {

  /**
   * Test the Forward form.
   */
  public function testForwardForm() {
    // Add the Forward link to articles.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/user-interface/forward');
    $edit = [
      'forward_node_types[article]' => 'article',
      'forward_view_modes[full]' => 'full',
      'forward_personal_message' => 2,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Navigate to the Forward Form.
    $article = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalLogin($this->forwardUser);
    $this->drupalGet('node/' . $article->id());
    $this->assertText(t('Email this article'), t('The article has a Forward link.'));
    $this->drupalGet('/forward/node/' . $article->id());
    $this->assertText(t('Forward this article to a friend'), 'The Forward form displays for an article.');

    // Submit the Forward form.
    $edit = [
      'email' => 'test@test.com',
      'name' => 'Test Forwarder',
      'recipient' => 'test@test.com',
      'message' => 'This is a test personal message.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Send Message'));
    $this->assertText(t('Thank you for spreading the word about Drupal.'), 'The Forward form displays a thank you message after submit.');

    // Submit the Forward form without a recipient.
    $this->drupalGet('/forward/node/' . $article->id());
    $edit = [
      'email' => 'test@test.com',
      'name' => 'Test Forwarder',
      'message' => 'This is a test personal message.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Send Message'));
    $this->assertText(t('Send to field is required.'), 'The Forward form displays an error message when the recipient is blank.');

    // Submit the Forward form without a personal message when required.
    $this->drupalGet('/forward/node/' . $article->id());
    $edit = [
      'email' => 'test@test.com',
      'name' => 'Test Forwarder',
      'recipient' => 'test@test.com',
    ];
    $this->drupalPostForm(NULL, $edit, t('Send Message'));
    $this->assertText(t('Your personal message field is required.'), 'The Forward form displays an error message when the message is blank and one is required.');

    // Submit the Forward form without a personal message when optional.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/user-interface/forward');
    $edit = [
      'forward_personal_message' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->drupalLogin($this->forwardUser);
    $this->drupalGet('/forward/node/' . $article->id());
    $edit = [
      'email' => 'test@test.com',
      'name' => 'Test Forwarder',
      'recipient' => 'test@test.com',
    ];
    $this->drupalPostForm(NULL, $edit, t('Send Message'));
    $this->assertNoText(t('Your personal message field is required.'), 'The Forward form does not display an error message when the message is blank and optional.');
  }

}
