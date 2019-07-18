<?php

namespace Drupal\tether_stats\Plugin\tether_stats\Chart;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tether_stats\TetherStatsManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tether_stats\TetherStatsChartRendererInterface;
use Drupal\tether_stats\Chart\TetherStatsChart;
use Drupal\tether_stats\Chart\TetherStatsComboChart;
use Drupal\tether_stats\Chart\TetherStatsPieChart;
use Drupal\tether_stats\Chart\TetherStatsSteppedChartSchema;
use Drupal\tether_stats\TetherStatsAnalytics;
use Drupal\user\PrivateTempStore;

/**
 * Chart renderer plugin for Google Charts.
 *
 * This plugin will render a chart object derived from TetherStatsChart into
 * a renderable array which will draw it using Google Charts.
 *
 * @TetherStatsChartRenderer(
 *   id = "tether_stats_google_charts",
 *   label = @Translation("Google Charts API")
 * )
 */
class TetherStatsChartRendererGoogle extends PluginBase implements TetherStatsChartRendererInterface, ContainerFactoryPluginInterface {

  /**
   * The Tether Stats manager service.
   *
   * @var \Drupal\tether_stats\TetherStatsManagerInterface
   */
  protected $manager;

  /**
   * Storage of private temporary data for the current user.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TetherStatsManagerInterface $manager, PrivateTempStore $temp_store) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->manager = $manager;
    $this->privateTempStore = $temp_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tether_stats.manager'),
      $container->get('user.private_tempstore')->get('tether_stats')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildChart(TetherStatsChart $chart, array $options = [], $iterate = FALSE) {

    $build = [];

    $schema = $chart->getSchema();

    if ($iterate) {

      // Store the schema in session so the iterator can access it later.
      $this->privateTempStore->set('chart_schema_' . $schema->id(), $schema);

      // Determine the active state of the iteration buttons depending on
      // available data.
      $start = $chart->getDateStart();
      $next = $schema->nextDateTime($start);
      $current_time = new \DateTime();
      $current_time->setTimestamp(REQUEST_TIME);

      $iterate = [
        'start' => $start,
        'previous' => ($start->getTimestamp() > $this->manager->getSettings()->get('advanced.first_activation_time')),
        'next' => ($next <= $current_time),
      ];
    }

    // Add the default chart options.
    $options += $this->getChartOptions($chart);

    $build = [
      '#theme' => 'tether_stats_chart_google',
      '#chart' => $chart,
      '#iterate' => $iterate,
      '#empty' => $this->t('There is currently no data to display.'),
      '#attached' => [
        'library' => [
          'tether_stats/tether_stats.chart.google.api',
          'tether_stats/tether_stats.chart.google',
          'tether_stats/tether_stats.chart',
        ],
        'drupalSettings' => [
          'tetherStatsGoogleChart' => [
            $chart->id() => [
              "type" => $chart->getClass(),
              "data" => $this->getDataTableWithTitleRow($chart),
              "options" => $options,
              "params" => [],
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Gets the data table to send to the Google charts API.
   *
   * Builds the data table to be passed into the arrayToDataTable
   * method of the Google charts API.
   *
   * @param TetherStatsChart $chart
   *   The chart object.
   *
   * @return array
   *   The data table.
   */
  public function getDataTable(TetherStatsChart $chart) {

    $data_table = $chart->getDataTable();

    if ($chart instanceof TetherStatsComboChart) {

      $schema = $chart->getSchema();

      // Change the unixtime for the step into a human readable label.
      foreach ($data_table as $inx => $row) {

        $data_table[$inx][0] = $this->getAxisStepLabel($schema, $data_table[$inx][0]);
      }
    }

    return $data_table;
  }

  /**
   * Adds a row of titles to the front of the data table.
   *
   * When first rendering charts, Google expects the first row to
   * be a row of titles for each column or slice.
   *
   * @param TetherStatsChart $chart
   *   The chart object.
   *
   * @return array
   *   The data table with title row.
   */
  protected function getDataTableWithTitleRow(TetherStatsChart $chart) {

    $data_table = $this->getDataTable($chart);
    $schema = $chart->getSchema();

    if ($chart instanceof TetherStatsComboChart) {

      // Add the column titles as the first row of the data table.
      $title_row = array_merge($schema->getChartableItemTitle(), $schema->getSeriesTitle());
      array_unshift($title_row, $schema->hAxisLabel);
      array_unshift($data_table, $title_row);
    }
    elseif ($chart instanceof TetherStatsPieChart) {

      $title_row = [$schema->sliceLabel, $schema->valueLabel];
      array_unshift($data_table, $title_row);
    }

    return $data_table;
  }

  /**
   * Gets an array of default options for the chart.
   *
   * @param TetherStatsChart $chart
   *   The chart to be rendered.
   *
   * @return array
   *   An array of options to be set when drawing the chart.
   */
  protected function getChartOptions(TetherStatsChart $chart) {

    $options = [];

    if ($chart instanceof TetherStatsComboChart) {

      $schema = $chart->getSchema();

      $num_columns = count($schema->getChartableItemSpec());
      $all_series = $schema->getSeriesSpec();

      foreach ($all_series as $inx => $series) {

        $options['series'][$num_columns + $inx] = [
          'type' => 'line',
        ];
      }

      $options['seriesType'] = 'bars';
      $options['hAxis'] = [
        'title' => $schema->hAxisLabel,
      ];
      $options['vAxis'] = [
        'title' => $schema->vAxisLabel,
      ];
    }

    return $options;
  }

  /**
   * Gets a human readable label for a domain axis step.
   *
   * @param \Drupal\tether_stats\Chart\TetherStatsSteppedChartSchema $schema
   *   The stepped chart schema.
   * @param int $step_unixtime
   *   The domain step time in unixtime.
   *
   * @return string
   *   The domain label at the $step position.
   */
  public static function getAxisStepLabel(TetherStatsSteppedChartSchema $schema, $step_unixtime) {

    $step = new \DateTime();
    $step->setTimestamp($step_unixtime);

    switch ($schema->domainStep) {

      case TetherStatsAnalytics::STEP_HOUR:
        $label = format_date($step->getTimestamp(), 'custom', 'j-H\h');
        break;

      case TetherStatsAnalytics::STEP_DAY:
        $label = format_date($step->getTimestamp(), 'custom', 'M j');
        break;

      case TetherStatsAnalytics::STEP_MONTH:
        $label = format_date($step->getTimestamp(), 'custom', 'M Y');
        break;

      case TetherStatsAnalytics::STEP_YEAR:
      default:
        $label = format_date($step->getTimestamp(), 'custom', 'Y');
        break;

    }
    return $label;
  }

}
