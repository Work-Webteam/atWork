<?php

namespace Drupal\forward\Tests;

/**
 * Test the Access Checker service.
 *
 * @group forward
 */
class AccessCheckerTest extends ForwardTestBase {

  /**
   * Test access to Forward links.
   */
  public function testAccessChecker() {
    // Add the Forward link to articles only.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/user-interface/forward');
    $edit = [
      'forward_node_types[article]' => 'article',
      'forward_view_modes[full]' => 'full',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $article = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalLogin($this->forwardUser);
    $this->drupalGet('node/' . $article->id());
    $this->assertText(t('Email this article'), 'The article has a Forward link.');

    // Users without "access forward" permission should not see the Forward link.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $article->id());
    $this->assertNoText(t('Email this article'), 'The article does not have a Forward link for a user without access forward permission.');

    // Users should not see the Forward link on full nodes when Forward is configured to display on Teasers only.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/user-interface/forward');
    $edit = [
      'forward_view_modes[full]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $article = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalLogin($this->forwardUser);
    $this->drupalGet('node/' . $article->id());
    $this->assertNoText(t('Email this article'), 'The article does not have a Forward link on a full article node when configured for teasers only.');

    // Basic pages should not have the Forward link.
    $page = $this->drupalCreateNode(['type' => 'page']);
    $this->drupalGet('node/' . $page->id());
    $this->assertNoText(t('Email this basic page'), 'The basic page does not have a Forward link.');

    // Users should not have the Forward link.
    $this->drupalLogin($this->adminUser);
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalGet('user/' . $user->id());
    $this->assertNoText(t('Email this user'), 'The user does not have a Forward link.');

    // Add the Forward link to users.
    $this->drupalGet('admin/config/user-interface/forward');
    $edit = [
      'forward_view_modes[full]' => 'full',
      'forward_entity_types[user]' => 'user',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalGet('user/' . $user->id());
    $this->assertText(t('Email this user'), 'The user has a Forward link after changing Forward settings.');

    // Remove the Forward link from articles.
    $article = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalLogin($this->forwardUser);
    $this->drupalGet('node/' . $article->id());
    $this->assertText(t('Email this article'), 'The article has a Forward link.');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/user-interface/forward');
    $edit = [
      'forward_node_types[article]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->drupalLogin($this->forwardUser);
    $article = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $article->id());
    $this->assertNoText(t('Email this article'), 'The article does not have a Forward link after configured for Users only.');
  }

}
