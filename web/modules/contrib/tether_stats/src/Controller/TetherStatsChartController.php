<?php

namespace Drupal\tether_stats\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tether_stats\TetherStatsManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\PrivateTempStore;

/**
 * Manages iteration AJAX callbacks for chart data.
 */
class TetherStatsChartController implements ContainerInjectionInterface {

  /**
   * The Tether Stats manager service.
   *
   * @var \Drupal\tether_stats\TetherStatsManagerInterface
   */
  protected $manager;

  /**
   * The error logger for the 'tether_stats' channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Storage of private temporary data for the current user.
   *
   * Used to temporarily hold chart session data for the purpose of iteration.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Constructor for a TetherStatsChartController.
   *
   * @param \Drupal\tether_stats\TetherStatsManagerInterface $manager
   *   The Tether Stats manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The log channel.
   * @param \Drupal\user\PrivateTempStore $temp_store
   *   The private temporary storage for tether_stats.
   */
  protected function __construct(TetherStatsManagerInterface $manager, LoggerInterface $logger, PrivateTempStore $temp_store) {

    $this->manager = $manager;
    $this->logger = $logger;
    $this->privateTempStore = $temp_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('tether_stats.manager'),
      $container->get('logger.channel.tether_stats'),
      $container->get('user.private_tempstore')->get('tether_stats')
    );
  }

  /**
   * Retrieves new chart data for iteration.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response
   */
  public function iterate() {

    $response_json = [
      'status' => FALSE,
    ];

    if (!empty($_GET['chart_id'])) {

      $storage_id = "chart_schema_{$_GET['chart_id']}";
      $chart_schema = $this->privateTempStore->get($storage_id);

      if (isset($chart_schema)) {

        if (!empty($_GET['start']) && is_numeric($_GET['start']) && !empty($_GET['direction']) && in_array($_GET['direction'], ['next', 'prev'])) {

          $date_start = new \DateTime();
          $date_start->setTimestamp($_GET['start']);

          // Iterate to the new start date.
          switch ($_GET['direction']) {

            case 'next':
              $date_start = $chart_schema->nextDateTime($date_start);
              break;

            case 'prev':
              $date_start = $chart_schema->previousDateTime($date_start);
              break;

          }

          $chart = $chart_schema->createChart($date_start, $this->manager->getAnalyticsStorage());

          $data = $this->manager->getChartRenderer()->getDataTable($chart);

          // Determine the active state of the iteration buttons depending on
          // available data.
          $next_start = $chart_schema->nextDateTime($date_start);
          $current_time = new \DateTime();
          $current_time->setTimestamp(REQUEST_TIME);

          $response_json = [
            'status' => TRUE,
            'data' => $data,
            'previous' => ($date_start->getTimestamp() > $this->manager->getSettings()->get('advanced.first_activation_time')),
            'next' => ($next_start <= $current_time),
            'start' => $date_start->getTimeStamp(),
          ];
        }
        else {

          $this->logger->error("Required start and direction query arguments missing or invalid when attempting to iterate chart {$_GET['chart_id']}.");
        }
      }
      else {

        $this->logger->error("Chart schema for chart Id {$_GET['chart_id']} was not found in PrivateTempStore for tether_stats and could not be iterated.");
      }
    }
    else {

      $this->logger->error("Attempted to iterate a chart but no chart id was specified.");
    }

    return new JsonResponse($response_json);
  }

}
