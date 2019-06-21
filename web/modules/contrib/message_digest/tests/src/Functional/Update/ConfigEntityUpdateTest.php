<?php

namespace Drupal\Tests\message_digest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Update hook test for the switch to config entities for intervals.
 *
 * @see message_digest_update_8101()
 *
 * @group message_digest
 */
class ConfigEntityUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->root . '/core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/message-digest-update-common.php',
      __DIR__ . '/../../../fixtures/update/message-digest-update-8101.php',
    ];
  }

  /**
   * Test the update hook.
   */
  public function testUpdate() {
    $this->runUpdates();

    // Verify that the config entities have been created.
    $entities = \Drupal::entityTypeManager()->getStorage('message_digest_interval')->loadMultiple();
    $this->assertCount(2, $entities);
    $this->assertArrayHasKey('daily', $entities);
    $this->assertArrayHasKey('weekly', $entities);

    /** @var \Drupal\message_notify\Plugin\Notifier\Manager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.message_notify.notifier.manager');
    $definitions = $plugin_manager->getDefinitions();

    // Verify that the 2 plugins exist.
    $this->assertArrayHasKey('message_digest:daily', $definitions);
    $this->assertArrayHasKey('message_digest:weekly', $definitions);
    $this->assertEquals('1 day', $definitions['message_digest:daily']['digest_interval']);
    $this->assertEquals('1 week', $definitions['message_digest:weekly']['digest_interval']);
  }

}
