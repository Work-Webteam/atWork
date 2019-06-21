<?php

namespace Drupal\Tests\message_digest\Functional\Form;

use Drupal\message_digest\Entity\MessageDigestInterval;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the message digest interval UI and forms.
 *
 * @group message_digest
 */
class MessageDigestIntervalFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'message_digest', 'system'];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Skip UID 1.
    $this->createUser();

    $this->adminUser = $this->createUser([
      'access administration pages',
      'administer message digest',
      'administer message templates',
    ]);

    $this->placeBlock('local_actions_block');
  }

  /**
   * Tests CRUD operations.
   */
  public function testCrud() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/message');
    $this->clickLink(t('Message digest intervals'));
    $this->clickLink(t('Add digest interval'));

    $edit = [
      'id' => 'bi_weekly',
      'label' => 'Every 2 weeks',
      'interval' => '2 weeks',
      'description' => 'Send digests every 2 weeks',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    /** @var \Drupal\message_digest\Entity\MessageDigestIntervalInterface $config */
    $config = MessageDigestInterval::load('bi_weekly');
    $this->assertEquals('2 weeks', $config->getInterval());
    $this->assertEquals('Every 2 weeks', $config->label());
    $this->assertEquals('Send digests every 2 weeks', $config->getDescription());

    $this->assertSession()->responseContains(t('Interval %label has been added.', ['%label' => $config->label()]));
    $this->assertSession()->addressEquals($config->toUrl('collection')->setAbsolute(TRUE)->toString());
    $this->assertSession()->pageTextContains('Every 2 weeks');

    // Edit the interval.
    $this->drupalGet($config->toUrl('edit-form'));
    $edit = [
      'label' => 'Every 14 days',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->addressEquals($config->toUrl('collection')->setAbsolute(TRUE)->toString());
    $this->assertSession()->responseContains(t('Interval %label has been updated.', ['%label' => 'Every 14 days']));

    // Try an invalid interval.
    $this->drupalGet($config->toUrl('edit-form'));
    $edit = [
      'interval' => '42 bananas',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains(t('The interval %interval is invalid', ['%interval' => $edit['interval']]));

    // Delete the interval.
    $this->drupalGet($config->toUrl('delete-form'));
    $this->assertSession()->responseContains(t('Delete %interval interval? This action cannot be undone.', ['%interval' => 'Every 14 days']));
    $this->drupalPostForm(NULL, [], t('Delete interval'));
    $this->assertSession()->responseContains(t('The %label message digest interval has been deleted.', ['%label' => 'Every 14 days']));
    $this->assertSession()->addressEquals($config->toUrl('collection')->setAbsolute(TRUE)->toString());
    \Drupal::entityTypeManager()->getStorage('message_digest_interval')->resetCache();
    $this->assertNull(MessageDigestInterval::load('bi_weekly'), 'The interval was not deleted.');
  }

}
