<?php

namespace Drupal\tether_stats\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\tether_stats\TetherStatsAnalytics;
use Drupal\tether_stats\TetherStatsIdentitySet;
use Drupal\tether_stats\TetherStatsIdentitySetInterface;
use Drupal\tether_stats\TetherStatsStorage;
use Drupal\tether_stats\TetherStatsAnalyticsStorage;
use Drupal\Core\Render\Element\MoreLink;

/**
 * Base test class for Tether Stats tests.
 *
 * Provides helper methods for generating test data.
 */
abstract class TetherStatsTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'tether_stats'];

  /**
   * Database connection.
   *
   * @var \Drupal\core\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer tether stats',
      'administer modules',
      'administer site configuration',
      'administer content types',
      'create page content',
      'delete any page content',
      'create article content',
      'delete any article content',
    ];

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // User to set up tether_stats.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);

    $this->database = $this->container->get('database');
  }

  /**
   * Generate a random URL string.
   *
   * @return string
   *   The random URL string.
   */
  protected function getRandomUrl() {

    return '/' . $this->randomMachineName(12) . '/' . $this->randomMachineName();
  }

  /**
   * Gets a random TetherStatsIdentitySet for testing.
   *
   * @param int $category
   *   The category of identity set to create as defined in
   *   TetherStatsIdentitySetInterface. May bitwise or '|' the constants
   *   together to create an element randomly from amongst the delineated
   *   categories.
   *
   * @return \Drupal\tether_stats\TetherStatsIdentitySet
   *   The random identity set.
   */
  protected function getRandomIdentitySet($category = 0b00000111) {

    if (empty($category)) {

      $category = 0b00000111;
    }

    $all_categories = [
      TetherStatsIdentitySetInterface::CATEGORY_NAME,
      TetherStatsIdentitySetInterface::CATEGORY_ENTITY,
      TetherStatsIdentitySetInterface::CATEGORY_URL,
    ];

    // Build a list of category options based on the bitwise $category
    // value.
    $categories = [];

    foreach ($all_categories as $test_category) {

      if ($category & $test_category) {

        $categories[] = $test_category;
      }
    }

    // Generate a random category from one of the provided categories.
    $category = $categories[mt_rand(0, count($categories) - 1)];

    switch ($category) {

      case TetherStatsIdentitySetInterface::CATEGORY_NAME:
        $identity_params = [
          'name' => $this->randomMachineName(12),
          'url' => $this->getRandomUrl(),
        ];
        break;

      case TetherStatsIdentitySetInterface::CATEGORY_ENTITY:

        $entity_types = ['node', 'user'];
        $entity_type = $entity_types[mt_rand(0, count($entity_types) - 1)];

        switch ($entity_type) {

          case 'node':
            $bundles = ['page', 'article'];
            $node = $this->drupalCreateNode(['type' => $bundles[mt_rand(0, count($bundles) - 1)], 'title' => "Node Entity", 'url' => $this->getRandomUrl()]);

            $identity_params = [
              'entity_type' => 'node',
              'entity_id' => $node->id(),
              'url' => $node->url(),
            ];
            break;

          case 'user':
            $bundles = ['page', 'article'];
            $user = $this->drupalCreateUser([], $this->randomMachineName(16));

            $identity_params = [
              'entity_type' => 'user',
              'entity_id' => $user->id(),
              'url' => '/user/' . $user->id(),
            ];
            break;

        }

        break;

      case TetherStatsIdentitySetInterface::CATEGORY_URL:
      default:
        $identity_params = [
          'url' => $this->getRandomUrl(),
        ];
        break;

    }

    return new TetherStatsIdentitySet($identity_params);
  }

  /**
   * Create random stats elements for testing.
   *
   * @param int $count
   *   The number of elements to create.
   * @param int $category
   *   The category of identity sets to create as defined in
   *   TetherStatsIdentitySetInterface. May bitwise or '|' the constants
   *   together to create an element randomly from amongst the delineated
   *   categories.
   *
   * @return array
   *   An array of created elements keyed by the element elid.
   */
  protected function createRandomElements($count, $category = 0b00000111) {

    $elements = [];
    $storage = $this->getStorage();

    for ($i = 0; $i < $count; $i++) {

      $element = $storage->createElementFromIdentitySet($this->getRandomIdentitySet($category));

      $elements[$element->getId()] = $element;
    }

    return $elements;
  }

  /**
   * Track an activity.
   *
   * @param int $elid
   *   The element id.
   * @param int $event_time
   *   The event time.
   * @param string $event_type
   *   The event type.
   * @param array $fields
   *   (Optional) An array of field values. If no key exists for browser,
   *   referrer or ip_address, a random option will be chosen.
   *
   * @return int
   *   The activity alid.
   */
  protected function createRandomActivity($elid, $event_time, $event_type = TetherStatsAnalytics::ACTIVITY_HIT, array $fields = []) {

    $browser_samples = [
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.69 Safari/537.36',
      'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.69 Safari/537.36',
      'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)',
      'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0',
    ];

    $ip_samples = [
      '192.168.5.1',
      '192.168.5.2',
      '192.168.5.3',
      '192.168.5.4',
    ];

    $referrer_samples = [
      'https://www.google.com/',
      'https://www.facebook.com/',
      'http://search.yahoo.com/search?p=my+search&fr=iphone&.tsrc=apple&pcarrier=AT%26T&pmcc=310&pmnc=410',
    ];

    $sid = $this->randomString(64);

    if (array_key_exists('browser', $fields)) {

      $browser = $fields['browser'];
    }
    else {

      $browser = $browser_samples[mt_rand(0, count($browser_samples) - 1)];
    }

    if (array_key_exists('ip_address', $fields)) {

      $ip = $fields['ip_address'];
    }
    else {

      $ip = $ip_samples[mt_rand(0, count($ip_samples) - 1)];
    }

    if (array_key_exists('referrer', $fields)) {

      $referrer = $fields['referrer'];
    }
    else {

      $referrer = $referrer_samples[mt_rand(0, count($referrer_samples) - 1)];
    }

    $uid = NULL;

    if (array_key_exists('uid', $fields)) {

      $uid = $fields['uid'];
    }

    return $this->getStorage()->trackActivity($elid, $event_type, $event_time, $ip, $sid, $browser, $referrer, $uid);
  }

  /**
   * Generate hit activity randomly spread over a list of elements.
   *
   * @param array $elids
   *   The array of element elids to create activity for.
   * @param int $count
   *   The amount of activity to generate.
   * @param int $start_time
   *   The inclusive start boundary for the event time.
   * @param int $finish_time
   *   The inclusive end boundary for the event time.
   * @param array $fields
   *   (Optional) An array of field values. If no key exists for browser,
   *   referrer or ip_address, a random option will be chosen.
   */
  protected function generateRandomHits(array $elids, $count, $start_time, $finish_time, array $fields = []) {

    for ($i = 0; $i < $count; $i++) {

      $event_time = mt_rand($start_time, $finish_time - 1);
      $elid = $elids[mt_rand(0, count($elids) - 1)];

      $this->createRandomActivity($elid, $event_time, TetherStatsAnalytics::ACTIVITY_HIT, $fields);
    }
  }

  /**
   * Gets the basic storage class.
   *
   * @return \Drupal\tether_stats\TetherStatsStorageInterface
   *   The storage object.
   */
  protected function getStorage() {
    $storage =& drupal_static(__FUNCTION__);

    if (!isset($storage)) {

      $storage = new TetherStatsStorage($this->database);
    }
    return $storage;
  }

  /**
   * Gets the analytics storage class.
   *
   * @return \Drupal\tether_stats\TetherStatsAnalyticsStorageInterface
   *   The analytics data mining storage object.
   */
  protected function getAnalyticsStorage() {
    $storage =& drupal_static(__FUNCTION__);

    if (!isset($storage)) {

      $storage = new TetherStatsAnalyticsStorage($this->database);
    }
    return $storage;
  }

  /**
   * Gets a unique natural number within the specified range.
   *
   * @param string $key
   *   Any future calls to this method with the same key will ensure
   *   uniqueness.
   * @param int $max
   *   The range max. Only applies when the $key is first used.
   * @param int $min
   *   The range min. Only applies when the $key is first used.
   *
   * @return int|null
   *   The unique natural number or null if there are no more
   *   unique numbers in range.
   */
  protected function getUniqueCount($key, $max = 10, $min = 0) {
    static $counts = [];

    if (!isset($counts[$key])) {

      $counts[$key] = range($min, $max - $min);
    }

    $unique_number = NULL;

    if (!empty($counts[$key])) {

      $inx = mt_rand(0, count($counts[$key]) - 1);

      $unique_number = $counts[$key][$inx];
      unset($counts[$key][$inx]);

      $counts[$key] = array_values($counts[$key]);
    }
    return $unique_number;
  }

}
