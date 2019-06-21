<?php

namespace Drupal\Tests\message_digest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the post-update hook that cleans up orphaned messages.
 *
 * @see message_digest_post_update_delete_orphaned_messages()
 *
 * @group message_digest
 */
class DeleteOrphanedMessagesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->root . '/core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/message-digest-update-common.php',
      __DIR__ . '/../../../fixtures/update/message-digest-post-update-delete-orphaned-messages.php',
    ];
  }

  /**
   * Tests the post-update hook.
   */
  public function testUpdate() {
    // There should be three rows in the message_digest table before executing
    // the update.
    $this->assertRowCount(3);

    // One of the rows contains an orphaned reference to a deleted user, and
    // another row an orphaned reference to a deleted message, so 1 row should
    // remain after running the updates.
    $this->runUpdates();
    $this->assertRowCount(1);

    // Check that the correct row remains. The correct row has the ID '3'.
    $ids = \Drupal::database()->select('message_digest', 'md')
      ->fields('md', ['id'])
      ->execute()
      ->fetchCol();
    $id = reset($ids);
    $this->assertEquals(3, $id);
  }

  /**
   * Checks that the message_digest table contains the expected number of rows.
   *
   * @param int $expected_count
   *   The expected number of rows.
   */
  protected function assertRowCount($expected_count) {
    $actual_count = \Drupal::database()->select('message_digest', 'md')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals($expected_count, $actual_count);
  }

}
