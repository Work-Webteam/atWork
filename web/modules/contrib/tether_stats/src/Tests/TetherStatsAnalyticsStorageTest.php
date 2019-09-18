<?php

namespace Drupal\tether_stats\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\tether_stats\TetherStatsAnalytics;
use Drupal\tether_stats\Chart\TetherStatsSteppedChartSchema;
use Drupal\tether_stats\TetherStatsAnalyticsStorageInterface;
use Drupal\tether_stats\TetherStatsIdentitySetInterface;
use Drupal\node\Entity\Node;
use Drupal\tether_stats\TetherStatsIdentitySet;

/**
 * Tests the analytics data mining storage.
 *
 * @group tether_stats
 */
class TetherStatsAnalyticsStorageTest extends TetherStatsTestBase {

  /**
   * Test the getTopElementsForActivity method.
   *
   * @see TetherStatsAnalyticsStorage::getTopElementsForActivity()
   */
  public function testGetTopElements() {

    $start_date = new \DateTime();
    $start_date->sub(new \DateInterval('P1D'));

    // Activity is aggregated in hourly chunks. The getTopElementsForActivity
    // method uses the aggregated hour counts for efficiency. However, without
    // normalizing the start date, activity that was added before the first
    // hour turnover would be missed.
    TetherStatsSteppedChartSchema::normalizeDate(TetherStatsAnalytics::STEP_HOUR, $start_date);

    $start = $start_date->getTimestamp();
    $finish = time();

    $num_top_elements = mt_rand(5, 10);

    // An array of what should be the top element elids.
    $top_element_elids = [];

    $elements = $this->createRandomElements($num_top_elements, TetherStatsIdentitySetInterface::CATEGORY_URL | TetherStatsIdentitySetInterface::CATEGORY_NAME);

    foreach ($elements as $elid => $element) {

      // Get a unique count so that no two elements will have the same
      // amount of activity which would make the top elids unpredictable.
      $top_element_elids[$elid] = $this->getUniqueCount('top', 20, 1);

      $this->generateRandomHits([$elid], $top_element_elids[$elid], $start, $finish);
    }

    // Sort from top performer downwards.
    arsort($top_element_elids);

    // Keep only the top 5.
    $top_element_elids = array_slice($top_element_elids, 0, 5, TRUE);

    // Add some additional activity outside of the active range.
    reset($top_element_elids);
    list($top_elid,) = each($top_element_elids);
    $this->generateRandomHits([$top_elid], 10, $start - 86400, $start - 100);

    // Extract the top elements within the active range  and test if
    // they line up with what they should be.
    $extracted_top_elements = $this->getAnalyticsStorage()->getTopElementsForActivity(TetherStatsAnalytics::ACTIVITY_HIT, $start, $finish, count($top_element_elids));
    $this->assertEqual($top_element_elids, $extracted_top_elements, 'Top Elements For Hit Activity Retrieved Successfully.');
  }

  /**
   * Tests the generic activity count methods.
   *
   * @see TetherStatsAnalyticsStorage::getAllActivityCount()
   * @see TetherStatsAnalyticsStorage::getElementActivityCount()
   */
  public function testGeneralActivityMethods() {

    $start_date = new \DateTime();
    $start_date->sub(new \DateInterval('P1D'));

    // Activity is aggregated in hourly chunks. The activity count
    // methods use the aggregated hour counts for efficiency. However, without
    // normalizing the start date, activity that was added before the first
    // hour turnover would be missed.
    TetherStatsSteppedChartSchema::normalizeDate(TetherStatsAnalytics::STEP_HOUR, $start_date);

    $start = $start_date->getTimestamp();
    $finish = time();

    // Create a base of elements to work with.
    $elements = $this->createRandomElements(20, TetherStatsIdentitySetInterface::CATEGORY_URL | TetherStatsIdentitySetInterface::CATEGORY_NAME);

    // Extract the first element for individual testing.
    $element = reset($elements);
    unset($elements[$element->getId()]);

    // Generate random hits on the segregated element.
    $element_count = mt_rand(5, 15);
    $this->generateRandomHits([$element->getId()], $element_count, $start, $finish);

    // Generate random hits on the other elements.
    $other_count = mt_rand(10, 50);
    $this->generateRandomHits(array_keys($elements), $other_count, $start, $finish);

    // Test element mining method.
    $count = $this->getAnalyticsStorage()->getElementActivityCount($element->getId(), TetherStatsAnalytics::ACTIVITY_HIT, $start, $finish);
    $this->assertEqual($element_count, $count, 'getElementActivityCount Retrieved Correct Counts.');

    // Test all mining method.
    $all_count = $this->getAnalyticsStorage()->getAllActivityCount(TetherStatsAnalytics::ACTIVITY_HIT, $start, $finish);
    $this->assertEqual($element_count + $other_count, $all_count, 'getAllActivityCount Retrieved Correct Counts.');

    // Test the chronologically sequenced variant.
    $counts = $this->getAnalyticsStorage()->getAllActivityCount(TetherStatsAnalytics::ACTIVITY_HIT, $start, $finish, TetherStatsAnalytics::STEP_HOUR);
    $total_count = array_sum($counts);

    $this->assertEqual($total_count, $all_count, 'getAllActivityCount Produced the Right Sum of Sequenced Results.');
  }

  /**
   * Tests the referrer methods.
   *
   * @see TetherStatsAnalyticsStorage::getHitActivityWithReferrerCount()
   * @see TetherStatsAnalyticsStorage::getElementHitActivityWithReferrerCount()
   */
  public function testActivityReferrerMethods() {

    $start = time() - 86400;
    $finish = time();

    // Create a base of elements to work with.
    $elements = $this->createRandomElements(20, TetherStatsIdentitySetInterface::CATEGORY_URL | TetherStatsIdentitySetInterface::CATEGORY_NAME);

    // Add activity with different referrer strings.
    $referrer_samples = [
      'https://www.google.com/',
      'https://www.facebook.com/',
      'http://search.yahoo.com/search?p=my+search&fr=iphone&.tsrc=apple&pcarrier=AT%26T&pmcc=310&pmnc=410',
    ];

    $referrer_searches = [
      'google',
      'facebook.com',
      'yahoo.com',
    ];

    $referrer_counts = [];

    foreach ($referrer_samples as $inx => $referrer) {

      $count = mt_rand(1, 6);
      $referrer_counts[$inx] = $count;

      $this->generateRandomHits(array_keys($elements), $count, $start, $finish, ['referrer' => $referrer]);
    }

    foreach ($referrer_samples as $inx => $referrer) {

      $total_count = $this->getAnalyticsStorage()->getHitActivityWithReferrerCount($referrer_searches[$inx], $start, $finish);
      $this->assertEqual($referrer_counts[$inx], $total_count, SafeMarkup::format('getHitActivityWithReferrerCount Retrieved Correct Counts for %search search.', ['%search' => $referrer_searches[$inx]]));
    }

    // Generate element specific activity.
    $start -= 200000;
    $finish -= 100000;
    $element = reset($elements);
    $elid = $element->getId();

    foreach ($referrer_samples as $inx => $referrer) {

      $count = mt_rand(1, 6);
      $referrer_counts[$inx] = $count;

      $this->generateRandomHits([$elid], $count, $start, $finish, ['referrer' => $referrer]);
    }

    foreach ($referrer_samples as $inx => $referrer) {

      $total_count = $this->getAnalyticsStorage()->getElementHitActivityWithReferrerCount($elid, $referrer_searches[$inx], $start, $finish);
      $this->assertEqual($referrer_counts[$inx], $total_count, SafeMarkup::format('getElementHitActivityWithReferrerCount Retrieved Correct Counts for %search search.', ['%search' => $referrer_searches[$inx]]));
    }
  }

  /**
   * Tests the browser methods.
   *
   * @see TetherStatsAnalyticsStorage::getHitActivityWithBrowserCount()
   * @see TetherStatsAnalyticsStorage::getElementHitActivityWithBrowserCount()
   */
  public function testActivityBrowserMethods() {

    $start = time() - 86400;
    $finish = time();

    // Create a base of elements to work with.
    $elements = $this->createRandomElements(20, TetherStatsIdentitySetInterface::CATEGORY_URL | TetherStatsIdentitySetInterface::CATEGORY_NAME);

    // Add activity with different browser strings.
    $browser_samples = [
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.69 Safari/537.36',
      'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)',
      'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0',
    ];

    $browser_searches = [
      'macintosh',
      'msie',
      'firefox',
    ];

    $browser_counts = [];

    foreach ($browser_samples as $inx => $browser) {

      $count = mt_rand(1, 6);
      $browser_counts[$inx] = $count;

      $this->generateRandomHits(array_keys($elements), $count, $start, $finish, ['browser' => $browser]);
    }

    foreach ($browser_searches as $inx => $search) {

      $total_count = $this->getAnalyticsStorage()->getHitActivityWithBrowserCount($search, $start, $finish);
      $this->assertEqual($browser_counts[$inx], $total_count, SafeMarkup::format('getHitActivityWithBrowserCount Retrieved Correct Counts for %search search.', ['%search' => $search]));
    }

    // Generate element specific activity.
    $start -= 200000;
    $finish -= 100000;
    $element = reset($elements);
    $elid = $element->getId();

    foreach ($browser_samples as $inx => $browser) {

      $count = mt_rand(1, 6);
      $browser_counts[$inx] = $count;

      $this->generateRandomHits([$elid], $count, $start, $finish, ['browser' => $browser]);
    }

    foreach ($browser_searches as $inx => $search) {

      $total_count = $this->getAnalyticsStorage()->getElementHitActivityWithBrowserCount($elid, $search, $start, $finish);
      $this->assertEqual($browser_counts[$inx], $total_count, SafeMarkup::format('getElementHitActivityWithBrowserCount Retrieved Correct Counts for %search search.', ['%search' => $search]));
    }
  }

  /**
   * Tests the impression count methods.
   *
   * @see TetherStatsAnalyticsStorage::getElementImpressedOnElementCount()
   * @see TetherStatsAnalyticsStorage::getElementImpressedOnNodeBundleCount()
   * @see TetherStatsAnalyticsStorage::getElementImpressedOnBaseUrlCount()
   * @see TetherStatsAnalyticsStorage::getAllElementsImpressedOnElementCount()
   * @see TetherStatsAnalyticsStorage::getElementImpressedAnywhereCount()
   */
  public function testImpressionMethods() {

    $start_date = new \DateTime();
    $start_date->sub(new \DateInterval('P1D'));

    // Activity is aggregated in hourly chunks. The
    // getElementImpressedAnywhereCount method uses the aggregated hour counts
    // for efficiency. However, without normalizing the start date, activity
    // that was added before the first hour turnover would be missed.
    TetherStatsSteppedChartSchema::normalizeDate(TetherStatsAnalytics::STEP_HOUR, $start_date);

    $start = $start_date->getTimestamp();
    $finish = time();

    // Create a base of elements to work with.
    $elements = $this->createRandomElements(3, TetherStatsIdentitySetInterface::CATEGORY_URL | TetherStatsIdentitySetInterface::CATEGORY_NAME);

    $elids = array_keys($elements);

    $source_element = $elements[$elids[0]];
    $impressed_element = $elements[$elids[1]];
    $second_impressed_element = $elements[$elids[2]];

    // Test the getElementImpressedOnElementCount method.
    $num_hits = mt_rand(3, 8);
    $impression_count = 0;
    $total_element_impressions = 0;

    for ($i = 0; $i < $num_hits; $i++) {

      $event_time = mt_rand($start, $finish - 1);

      $alid = $this->createRandomActivity($source_element->getId(), $event_time, TetherStatsAnalytics::ACTIVITY_HIT);

      $times_impressed = mt_rand(0, 5);

      for ($j = 0; $j < $times_impressed; $j++) {

        $this->getStorage()->trackImpression($impressed_element->getId(), $alid, $event_time);
      }
      $impression_count += $times_impressed;
    }

    // See if the database count matches.
    $count = $this->getAnalyticsStorage()->getElementImpressedOnElementCount($impressed_element->getId(), $source_element->getId(), $start, $finish);
    $this->assertEqual($count, $impression_count, 'Method getElementImpressedOnElementCount produced correct count.');

    $total_element_impressions += $impression_count;

    // Test the getAllElementsImpressedOnElementCount method.
    $num_hits = mt_rand(3, 8);
    $second_impression_count = 0;

    for ($i = 0; $i < $num_hits; $i++) {

      $event_time = mt_rand($start, $finish - 1);

      $alid = $this->createRandomActivity($source_element->getId(), $event_time, TetherStatsAnalytics::ACTIVITY_HIT);

      $times_impressed = mt_rand(0, 5);

      for ($j = 0; $j < $times_impressed; $j++) {

        $this->getStorage()->trackImpression($second_impressed_element->getId(), $alid, $event_time);
      }
      $second_impression_count += $times_impressed;
    }

    // See if the database count matches.
    $count = $this->getAnalyticsStorage()->getAllElementsImpressedOnElementCount($source_element->getId(), $start, $finish);
    $this->assertEqual($count, $impression_count + $second_impression_count, 'Method getAllElementsImpressedOnElementCount produced correct count.');

    // Test the getElementImpressedOnNodeBundleCount method.
    $source_element = NULL;

    // Create some entity bound elements.
    $entity_elements = $this->createRandomElements(10, TetherStatsIdentitySetInterface::CATEGORY_ENTITY);

    foreach ($entity_elements as $element) {

      if ($element->getIdentityParameter('entity_type') == 'node') {

        $node = Node::load($element->getIdentityParameter('entity_id'));
        $bundle = $node->bundle();
        $source_element = $element;
        break;
      }
    }

    // Create a simple source element bound to a node if one was not
    // created randomly.
    if (!isset($source_element)) {

      $bundle = 'page';
      $node = $this->drupalCreateNode(['type' => $bundle, 'title' => "Node Entity", 'url' => $this->getRandomUrl()]);

      $source_element = $this->getStorage()->createElementFromIdentitySet(new TetherStatsIdentitySet([
        'entity_type' => 'node',
        'entity_id' => $node->id(),
        'url' => $node->url(),
      ]));
    }

    $num_hits = mt_rand(3, 8);
    $impression_count = 0;

    for ($i = 0; $i < $num_hits; $i++) {

      $event_time = mt_rand($start, $finish - 1);
      $alid = $this->createRandomActivity($source_element->getId(), $event_time, TetherStatsAnalytics::ACTIVITY_HIT);

      $times_impressed = mt_rand(0, 5);

      for ($j = 0; $j < $times_impressed; $j++) {

        $this->getStorage()->trackImpression($impressed_element->getId(), $alid, $event_time);
      }
      $impression_count += $times_impressed;
    }

    // See if the database count matches.
    $count = $this->getAnalyticsStorage()->getElementImpressedOnNodeBundleCount($impressed_element->getId(), $bundle, $start, $finish);
    $this->assertEqual($count, $impression_count, 'Method getElementImpressedOnNodeBundleCount produced correct count.');

    $total_element_impressions += $impression_count;

    // Test the base url method.
    $base_url = '/' . $this->randomMachineName(10);

    $source_elements = [];
    $num_urls = mt_rand(2, 6);

    for ($i = 0; $i < $num_urls; $i++) {

      $source_elements[] = $this->getStorage()->createElementFromIdentitySet(new TetherStatsIdentitySet([
        'url' => $base_url . $this->randomMachineName(4) . '/' . $this->randomMachineName(8),
      ]));
    }

    $impression_count = 0;

    foreach ($source_elements as $source_element) {

      $num_hits = mt_rand(3, 8);

      for ($i = 0; $i < $num_hits; $i++) {

        $event_time = mt_rand($start, $finish - 1);
        $alid = $this->createRandomActivity($source_element->getId(), $event_time, TetherStatsAnalytics::ACTIVITY_HIT);

        $times_impressed = mt_rand(0, 5);

        for ($j = 0; $j < $times_impressed; $j++) {

          $this->getStorage()->trackImpression($impressed_element->getId(), $alid, $event_time);
        }
        $impression_count += $times_impressed;
      }
    }

    // See if the database count matches.
    $count = $this->getAnalyticsStorage()->getElementImpressedOnBaseUrlCount($impressed_element->getId(), $base_url, $start, $finish);
    $this->assertEqual($count, $impression_count, 'Method getElementImpressedOnBaseUrlCount produced correct count.');

    $total_element_impressions += $impression_count;

    // See if the impressed element total impressed count matches,
    // now that it's been impressed several times above.
    $count = $this->getAnalyticsStorage()->getElementImpressedAnywhereCount($impressed_element->getId(), $start, $finish);
    $this->assertEqual($count, $total_element_impressions, 'Method getElementImpressedAnywhereCount produced correct count.');

  }

}
