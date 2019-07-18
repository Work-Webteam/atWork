<?php

namespace Drupal\tether_stats\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\tether_stats\Chart\TetherStatsSteppedChartSchema;
use Drupal\tether_stats\TetherStatsAnalytics;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tether_stats\Chart\TetherStatsComboChartSchema;
use Drupal\tether_stats\Chart\TetherStatsComboChart;
use Drupal\tether_stats\Chart\TetherStatsPieChartSchema;
use Drupal\tether_stats\Chart\TetherStatsPieChart;
use Drupal\tether_stats\TetherStatsElement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tether_stats\TetherStatsManagerInterface;
use Drupal\Core\Url;

/**
 * Generates an overview page with general stats information.
 */
class TetherStatsOverviewController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The Tether Stats manager service.
   *
   * @var \Drupal\tether_stats\TetherStatsManagerInterface
   */
  protected $manager;

  /**
   * Constructor for a TetherStatsOverviewController.
   *
   * @param \Drupal\tether_stats\TetherStatsManagerInterface $manager
   *   The Tether Stats manager service.
   */
  protected function __construct(TetherStatsManagerInterface $manager) {

    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static($container->get('tether_stats.manager'));
  }

  /**
   * Controller callback for the stats overview page.
   *
   * @return array
   *   The page build array.
   */
  public function overviewPage() {

    if ($this->manager->isActive()) {

      $build = [
        '#theme' => 'tether_stats_overview_page',
      ];

      $finish_date = new \DateTime();
      $finish_date->setTimestamp(REQUEST_TIME);

      // Borrow a stepped chart method to normalize the current time to
      // the start of the current day.
      TetherStatsSteppedChartSchema::normalizeDate(TetherStatsAnalytics::STEP_DAY, $finish_date);

      // Make a start_date clone and rewind to the start of yesterday.
      $start_date = clone $finish_date;
      $start_date->sub(new \DateInterval('P1D'));

      $analytics = $this->manager->getAnalyticsStorage();

      // Get the top 10 most viewed pages yesterday.
      $top_results = $analytics->getTopElementsForActivity(TetherStatsAnalytics::ACTIVITY_HIT, $start_date->getTimestamp(), $finish_date->getTimestamp(), 10);

      $add_query_column = $this->manager->getSettings()->get('allow_query_string_elements');

      $elements = [];
      $rows = [];

      if (!empty($top_results)) {

        foreach ($top_results as $elid => $count) {

          $elements[$elid] = TetherStatsElement::loadElement($elid);

          $row = [
            $elements[$elid]->getId(),
            $elements[$elid]->getIdentityParameter('url'),
            $count,
          ];

          $query = $elements[$elid]->getIdentityParameter('query');

          if ($add_query_column) {

            array_splice($row, 2, 0, isset($query) ? $query : '');
          }

          $rows[] = $row;
        }
      }

      $header = [
        $this->t('Element Id'),
        $this->t('Page Url'),
        $this->t('Total Hits'),
      ];

      if ($add_query_column) {

        array_splice($header, 2, 0, $this->t('Query String'));
      }

      $build['top_hit_table'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('There is insufficient data to display at this time.'),
      ];

      $combo_chart_schema = new TetherStatsComboChartSchema('tether_stats-chart-combo', $this->t('Hour'), $this->t('Hits'));

      // Have the chart determine how many x-axis ticks to use and which domain
      // step such as hourly, daily, monthly, or yearly. In this case, it should
      // use the hourly domain step as we are only showing one day.
      $combo_chart_schema->calcDomainStep($start_date, $finish_date);

      if (!empty($top_results)) {

        // Show a chart of the top 3 pages broken down hourly yesterday.
        $elids = array_slice(array_keys($top_results), 0, 3);

        // Add a column for each of the top 3 elements.
        foreach ($elids as $elid) {

          $combo_chart_schema->addElementItem($elid, TetherStatsAnalytics::ACTIVITY_HIT, $elements[$elid]->getIdentityParameter('url'));
        }

        $combo_chart_schema->addMeanLineSeries($this->t('Mean'));
      }

      // Create a chart from the schema. The schema tells the chart how to
      // extract and organize the stats data, but the data itself is queried by
      // the chart object. This allows chart data to be iterated over different
      // time periods without having to reconstruct the schema every time.
      $combo_chart = new TetherStatsComboChart($combo_chart_schema, $start_date, $analytics);

      // Prepare a pie chart comparing the top 10 pages.
      $pie_chart_schema = new TetherStatsPieChartSchema('tether_stats-chart-pie', $start_date->diff($finish_date));

      if (!empty($top_results)) {

        $elids = array_slice(array_keys($top_results), 0, 5);

        // Add a slice for each of the top 5 elements.
        foreach ($elids as $elid) {

          $pie_chart_schema->addElementItem($elid, TetherStatsAnalytics::ACTIVITY_HIT, $elements[$elid]->getIdentityParameter('url'));
        }
      }

      $pie_chart = new TetherStatsPieChart($pie_chart_schema, $start_date, $analytics);

      // Get the API specific chart renderer, such as the one for Google Charts.
      $chart_renderer = $this->manager->getChartRenderer();

      // Prepare the combo chart and configure it for iteration.
      $build['top_hit_combo_chart'] = $chart_renderer->buildChart($combo_chart, [], TRUE);

      // Prepare the pie chart without iteration.
      $build['top_hit_pie_chart'] = $chart_renderer->buildChart($pie_chart, [], FALSE);
    }
    else {

      $build = [
        '#markup' => '<p>' . $this->t('Stats collection is currently turned off. To start collecting page hits and other statistics data, please visit the <a href="@settings_url">settings</a> page.', ['@settings_url' => Url::fromRoute('tether_stats.settings_form')->toString()]) . '</p>',
      ];
    }

    return $build;
  }

  /**
   * Controller callback for an element specific overview page.
   *
   * Shows a table of hit activity for the element over the past week.
   *
   * @return array
   *   The page build array.
   */
  public function elementOverviewPage() {

    if ($this->manager->isActive()) {

      if (!empty($_GET['elid']) && is_numeric($_GET['elid'])) {

        $elid = $_GET['elid'];
        $element = $this->manager->getStorage()->loadElement($elid);

        if (isset($element)) {

          $build = [
            '#theme' => 'tether_stats_element_overview_page',
            '#element_details' => [
              'id' => $element->getId(),
            ],
          ];

          $build['#element_details'] += $element->getIdentityParameters();

          if ($element->hasIdentityParameter('name')) {

            $build['#element_details']['type'] = $this->t('Unique Name Identifier');
          }
          elseif ($element->hasIdentityParameter('entity_id')) {

            $build['#element_details']['type'] = $this->t('Entity');
          }
          else {

            $build['#element_details']['type'] = $this->t('Url');
          }

          $finish_date = new \DateTime();
          $finish_date->setTimestamp(REQUEST_TIME);

          // Borrow a stepped chart method to normalize the current time to
          // the start of the current day.
          TetherStatsSteppedChartSchema::normalizeDate(TetherStatsAnalytics::STEP_DAY, $finish_date);

          // Include the current day in the initial results.
          $finish_date->add(new \DateInterval('P1D'));

          // Make a start_date clone and rewind to one week ago.
          $start_date = clone $finish_date;
          $start_date->sub(new \DateInterval('P7D'));

          $combo_chart_schema = new TetherStatsComboChartSchema('tether_stats-chart-element-combo', $this->t('Day'), $this->t('Hits'));

          // Have the chart determine how many x-axis ticks to use and which
          // domain step such as hourly, daily, monthly, or yearly. In this
          // case, it should use the daily domain step as we are showing data
          // over one week.
          $combo_chart_schema->calcDomainStep($start_date, $finish_date);

          $combo_chart_schema->addElementItem($elid, TetherStatsAnalytics::ACTIVITY_HIT, $this->t('Element @id', ['@id' => $elid]));

          // Create a chart from the schema. The schema tells the chart how to
          // extract and organize the stats data, but the data itself is queried
          // by the chart object. This allows chart data to be iterated over
          // different time periods without having to reconstruct the schema
          // every time.
          $combo_chart = new TetherStatsComboChart($combo_chart_schema, $start_date, $this->manager->getAnalyticsStorage());

          // Get the API specific chart renderer, such as the one for Google
          // Charts.
          $chart_renderer = $this->manager->getChartRenderer();

          // Prepare the combo chart and configure it for iteration.
          $build['combo_chart'] = $chart_renderer->buildChart($combo_chart, [], TRUE);
        }
        else {

          $build = [
            '#markup' => '<p>' . $this->t('Element not found.') . '</p>',
          ];
        }
      }
      else {

        $build = [
          '#markup' => '<p>' . $this->t('Invalid element elid, or elid not specified.') . '</p>',
        ];
      }
    }
    else {

      $build = [
        '#markup' => '<p>' . $this->t('Stats collection is currently turned off. To start collecting page hits and other statistics data, please visit the <a href="@settings_url">settings</a> page.', ['@settings_url' => Url::fromRoute('tether_stats.settings_form')->toString()]) . '</p>',
      ];
    }

    return $build;
  }

}
