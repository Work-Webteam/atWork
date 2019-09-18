<?php

namespace Drupal\tether_stats\Tests;

use Drupal\tether_stats\TetherStatsIdentitySetInterface;
use Drupal\tether_stats\TetherStatsAnalytics;
use Drupal\tether_stats\Chart\TetherStatsSteppedChartSchema;
use Drupal\tether_stats\Entity\TetherStatsDerivative;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the activity tracking data storage.
 *
 * @group tether_stats
 */
class TetherStatsStorageTest extends TetherStatsTestBase {

  /**
   * Test methods related to creating and loading elements.
   */
  public function testElementConstruction() {

    // Create name based identity set.
    $identity_set = $this->getRandomIdentitySet(TetherStatsIdentitySetInterface::CATEGORY_NAME);

    // Create element.
    $element = $this->getStorage()->createElementFromIdentitySet($identity_set);
    $this->assertTrue(isset($element), SafeMarkup::format('Created name based element with name %name.', ['%name' => $identity_set->get('name')]));

    // Create another element from same set.
    $new_element = $this->getStorage()->createElementFromIdentitySet($identity_set);
    $this->assertTrue($element->getId() == $new_element->getId(), 'Creating name based element from identical set did not invoke a new element entry.');

    // Create entity bound identity set.
    $identity_set = $this->getRandomIdentitySet(TetherStatsIdentitySetInterface::CATEGORY_ENTITY);

    // Create element.
    $element = $this->getStorage()->createElementFromIdentitySet($identity_set);
    $this->assertTrue(isset($element), SafeMarkup::format('Created entity bound element of entity type %type.', ['%type' => $identity_set->get('entity_type')]));

    // Create another element from same set.
    $new_element = $this->getStorage()->createElementFromIdentitySet($identity_set);
    $this->assertTrue($element->getId() == $new_element->getId(), 'Creating entity bound element from identical set did not invoke a new element entry.');

    // Create url based identity set.
    $identity_set = $this->getRandomIdentitySet(TetherStatsIdentitySetInterface::CATEGORY_URL);

    // Create element.
    $element = $this->getStorage()->createElementFromIdentitySet($identity_set);
    $this->assertTrue(isset($element), SafeMarkup::format('Created url based element with url %url.', ['%url' => $identity_set->get('url')]));

    // Create another element from same set.
    $new_element = $this->getStorage()->createElementFromIdentitySet($identity_set);
    $this->assertTrue($element->getId() == $new_element->getId(), 'Creating url based element from identical set did not invoke a new element entry.');

    // Create new identity set.
    $identity_set = $this->getRandomIdentitySet();

    // Test loading an element from a new, unused identity set.
    $element = $this->getStorage()->loadElementFromIdentitySet($identity_set);
    $this->assertFalse(isset($element), 'Loading an element from a new identity set did not invoke a new element entry.');

    // Test loading an element from a previously used identity set.
    $element = $this->getStorage()->createElementFromIdentitySet($identity_set);
    $element = $this->getStorage()->loadElementFromIdentitySet($identity_set);
    $this->assertTrue(isset($element), 'Loading an element from an identity set that was used prior was successful.');

    // Load an element by Id.
    $element = $this->getStorage()->loadElement($element->getId());
    $this->assertTrue(isset($element), 'Loading an element from its id was successful.');

  }

  /**
   * Test methods related to derivatives.
   */
  public function testDerivatives() {

    // Create test derivative.
    $derivative_entity = TetherStatsDerivative::create(['name' => 'derivative-simple', 'derivativeEntityType' => '*', 'derivativeBundle' => '*']);
    $derivative_entity->save();

    $set = $this->getRandomIdentitySet();
    $set->set('derivative', 'derivative-simple');
    $element = $this->getStorage()->createElementFromIdentitySet($set);
    $this->assert(isset($element), 'Successfully created an element with a derivative.');

    // Test derivative usage.
    $num_elements = mt_rand(1, 5);

    // Create one less than $num_elements, as one element was already
    // created for the previous test.
    for ($i = 0; $i < $num_elements - 1; $i++) {

      $set = $this->getRandomIdentitySet();
      $set->set('derivative', 'derivative-simple');

      $this->getStorage()->createElementFromIdentitySet($set);
    }

    $count = $this->getStorage()->getDerivativeUsageCount('derivative-simple');

    $this->assertEqual($count, $num_elements, SafeMarkup::format('Derivative usage count %count was counted correctly.', ['%count' => $count]));

  }

  /**
   * Test activity and impression tracking methods.
   */
  public function testTracking() {

    // Set event time and associated normalized times.
    $time = new \DateTime();

    $hour = clone $time;
    TetherStatsSteppedChartSchema::normalizeDate('hour', $hour);
    $day = clone $time;
    TetherStatsSteppedChartSchema::normalizeDate('day', $day);
    $month = clone $time;
    TetherStatsSteppedChartSchema::normalizeDate('month', $month);
    $year = clone $time;
    TetherStatsSteppedChartSchema::normalizeDate('year', $year);

    $elements = $this->createRandomElements(2);

    $source_element = array_shift($elements);
    $impressed_element = array_shift($elements);

    $fake_sid = $this->randomString(64);

    $alid_a = $this->getStorage()->trackActivity($source_element->getId(), TetherStatsAnalytics::ACTIVITY_HIT, $time->getTimestamp(), '127.0.0.1', $fake_sid, 'browser A', 'referrer', 1);

    // Confirm tracked activity.
    $activity = $this->database->select('tether_stats_activity_log', 'a')
      ->fields('a')
      ->condition('a.alid', $alid_a)
      ->execute()
      ->fetch();

    $this->assertTrue(!empty($activity), 'An activity was recorded successfully.');

    $this->assertEqual($activity->type, TetherStatsAnalytics::ACTIVITY_HIT, 'Type field same in recorded activity.');
    $this->assertEqual($activity->uid, 1, 'Uid field same in recorded activity.');
    $this->assertEqual($activity->referrer, 'referrer', 'Referrer field same in recorded activity.');
    $this->assertEqual($activity->ip_address, '127.0.0.1', 'IP address field same in recorded activity.');
    $this->assertEqual($activity->sid, $fake_sid, 'Session Id field same in recorded activity.');
    $this->assertEqual($activity->browser, 'browser A', 'Browser field same in recorded activity.');
    $this->assertEqual($activity->created, $time->getTimestamp(), 'Created field same in recorded activity.');

    $this->assertEqual($activity->hour, $hour->getTimestamp(), 'Hour field correct in recorded activity.');
    $this->assertEqual($activity->day, $day->getTimestamp(), 'Day field correct in recorded activity.');
    $this->assertEqual($activity->month, $month->getTimestamp(), 'Month field correct in recorded activity.');
    $this->assertEqual($activity->year, $year->getTimestamp(), 'Year field correct in recorded activity.');

    // Track second activity.
    $this->getStorage()->trackActivity($source_element->getId(), TetherStatsAnalytics::ACTIVITY_HIT, $time->getTimestamp(), '127.0.0.1', $fake_sid, 'browser B', 'referrer', 1);

    // Confirm hour count.
    $count = $this->database->select('tether_stats_hour_count', 'h')
      ->fields('h', ['count'])
      ->condition('h.elid', $source_element->getId())
      ->condition('hour', $hour->getTimestamp())
      ->condition('type', TetherStatsAnalytics::ACTIVITY_HIT)
      ->execute()
      ->fetchField();

    $this->assertTrue(!empty($count) && $count == 2, 'Hour count instantiated and incremented correctly.');

    // Track impression.
    $this->getStorage()->trackImpression($impressed_element->getId(), $alid_a, $time->getTimestamp());

    // Confirm impression in log.
    $impressions = $this->container->get('database')->select('tether_stats_impression_log', 'i')
      ->condition('i.alid', $alid_a)
      ->condition('i.elid', $impressed_element->getId())
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertTrue($impressions == 1, 'Impression logged correctly.');

    // Confirm impression hour count.
    $count = $this->database->select('tether_stats_hour_count', 'h')
      ->fields('h', ['count'])
      ->condition('h.elid', $impressed_element->getId())
      ->condition('hour', $hour->getTimestamp())
      ->condition('type', TetherStatsAnalytics::ACTIVITY_IMPRESS)
      ->execute()
      ->fetchField();

    $this->assertTrue(!empty($count) && $count == 1, 'Hour count recorded for impression correctly.');
  }

}
