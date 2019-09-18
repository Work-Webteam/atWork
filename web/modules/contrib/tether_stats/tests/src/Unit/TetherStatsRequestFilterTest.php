<?php

namespace Drupal\Tests\tether_stats\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tether_stats\TetherStatsRequestFilter;
use Drupal\tether_stats\TetherStatsRequestFilterInterface;

/**
 * @coversDefaultClass \Drupal\tether_stats\TetherStatsRequestFilter
 *
 * @group tether_stats
 */
class TetherStatsRequestFilterTest extends UnitTestCase {

  /**
   * @covers ::doesUrlMatchRule
   *
   * @dataProvider urlFilterTestProvider
   *
   * @param string $url
   *   The url to test with.
   * @param string $rule
   *   The filter rule to test against.
   * @param bool $expected
   *   The expected match.
   */
  public function testUrlMatchRule($url, $rule, $expected) {

    $this->assertEquals($expected, TetherStatsRequestFilter::doesUrlMatchRule(explode('/', $url), explode('/', $rule)));
  }

  /**
   * @covers ::doesUrlMatchRule
   *
   * @dataProvider routeFilterTestProvider
   *
   * @param string $route
   *   The route name to test with.
   * @param string $rule
   *   The filter rule to test against.
   * @param bool $expected
   *   The expected match.
   */
  public function testRouteMatchRule($route, $rule, $expected) {

    $this->assertEquals($expected, TetherStatsRequestFilter::doesRouteMatchRule(explode('.', $route), explode('.', $rule)));
  }

  /**
   * @covers ::isUrlFiltered
   */
  public function testIsUrlFiltered() {

    $rules = [
      'admin/config',
      'node/#/edit',
    ];

    // Test exclusion mode.
    $filter = new TetherStatsRequestFilter([], $rules, TetherStatsRequestFilterInterface::MODE_EXCLUDE);

    $this->assertTrue($filter->isUrlFiltered('admin/config/system'));
    $this->assertTrue($filter->isUrlFiltered('node/2/edit'));
    $this->assertFalse($filter->isUrlFiltered('admin/structure'));
    $this->assertFalse($filter->isUrlFiltered('node/2'));

    // Test inclusion mode.
    $filter = new TetherStatsRequestFilter([], $rules, TetherStatsRequestFilterInterface::MODE_INCLUDE);

    $this->assertFalse($filter->isUrlFiltered('admin/config/system'));
    $this->assertFalse($filter->isUrlFiltered('node/2/edit'));
    $this->assertTrue($filter->isUrlFiltered('admin/structure'));
    $this->assertTrue($filter->isUrlFiltered('node/2'));
  }

  /**
   * @covers ::isRouteFiltered
   */
  public function testIsRouteFiltered() {

    $rules = [
      'entity.*',
      'tether_stats.overview',
    ];

    // Test exclusion mode.
    $filter = new TetherStatsRequestFilter($rules, [], TetherStatsRequestFilterInterface::MODE_EXCLUDE);

    $this->assertTrue($filter->isRouteFiltered('entity.tether_stats_derivative.collection'));
    $this->assertTrue($filter->isRouteFiltered('tether_stats.overview'));
    $this->assertFalse($filter->isRouteFiltered('entity'));
    $this->assertFalse($filter->isRouteFiltered('tether_stats.overview.element'));

    // Test inclusion mode.
    $filter = new TetherStatsRequestFilter($rules, [], TetherStatsRequestFilterInterface::MODE_INCLUDE);

    $this->assertTrue($filter->isRouteFiltered('entity'));
    $this->assertTrue($filter->isRouteFiltered('tether_stats.overview.element'));
    $this->assertFalse($filter->isRouteFiltered('entity.tether_stats_derivative.collection'));
    $this->assertFalse($filter->isRouteFiltered('tether_stats.overview'));
  }

  /**
   * Data provider for testRouteMatchRule.
   *
   * @return array
   *   The test data.
   */
  public function urlFilterTestProvider() {

    return [
      ['node/1', 'node', TRUE],
      ['node/1', 'node/#', TRUE],
      ['node/1', 'node/#/edit', FALSE],
      ['node/a', 'node/#', FALSE],
      ['node/a', 'node/%', TRUE],
      ['node/a/b', 'node/%', TRUE],
      ['node/a', 'node/%/edit', FALSE],
      ['admin/config/tether_stats/overview', 'admin', TRUE],
      ['admin/config/tether_stats/overview', 'admin/%', TRUE],
      ['admin/config/tether_stats/overview', 'admin/#', FALSE],
      ['admin/config/tether_stats/overview', 'admin/%/tether_stats', TRUE],
      ['admin/config/tether_stats/overview', 'admin/%/tether_stats/%', TRUE],
      ['admin/config/tether_stats/overview', 'admin/%/tether_stats/%/apple', FALSE],
      ['admin/config/tether_stats/overview', 'admin/%/tether_stats/%/%', FALSE],
      ['admin/config/tether_stats/overview', 'admin/%/%/%', TRUE],
      ['admin/config/tether_stats/overview', 'admin/%/%/%/%', FALSE],
    ];
  }

  /**
   * Data provider for testRouteMatchRule.
   *
   * @return array
   *   The test data.
   */
  public function routeFilterTestProvider() {

    return [
      ['entity.node.canonical', '*', TRUE],
      ['entity.node.canonical', '*.*', TRUE],
      ['entity.node.canonical', '*.*.*', TRUE],
      ['entity.node.canonical', '*.*.*.*', FALSE],
      ['entity.node.canonical', 'entity', FALSE],
      ['entity.node.canonical', 'entity.node', FALSE],
      ['entity.node.canonical', 'entity.*', TRUE],
      ['entity.node.canonical', 'entity.*.canonical', TRUE],
      ['entity.node.canonical', 'entity.*.test', FALSE],
      ['entity.node.canonical', 'entity.node.canonical.*', FALSE],
      ['entity.node.canonical', '*.node', FALSE],
      ['entity.node.canonical', '*.entity.node', FALSE],
      ['entity.node.canonical', '*.*.canonical', TRUE],
      ['entity.node.canonical', '*.node.canonical', TRUE],
    ];
  }

}
