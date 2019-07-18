<?php

namespace Drupal\tether_stats\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\tether_stats\TetherStatsManagerInterface;
use Drupal\tether_stats\TetherStatsIdentitySet;
use Psr\Log\LoggerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tether_stats\TetherStatsIdentitySetInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\tether_stats\TetherStatsElementInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\tether_stats\TetherStatsAnalytics;

/**
 * Provides route response for tracking activities.
 */
class TetherStatsTrackController implements ContainerInjectionInterface {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor for a TetherStatsTrackController.
   *
   * @param \Drupal\tether_stats\TetherStatsManagerInterface $manager
   *   The Tether Stats manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The log channel.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  protected function __construct(TetherStatsManagerInterface $manager, LoggerInterface $logger, AccountProxyInterface $account, RequestStack $request_stack) {

    $this->manager = $manager;
    $this->logger = $logger;
    $this->account = $account;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('tether_stats.manager'),
      $container->get('logger.channel.tether_stats'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * Get a list of all possible activity types to track.
   *
   * @return array
   *   The list of allowable types.
   */
  public static function allowableActivityTypes() {

    return [TetherStatsAnalytics::ACTIVITY_HIT, TetherStatsAnalytics::ACTIVITY_CLICK, TetherStatsAnalytics::ACTIVITY_IMPRESS];
  }

  /**
   * Controller callback to track an activity.
   *
   * This callback logs activities and impressions in the database based on
   * instructions in the query string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response
   */
  public function track($event_time = REQUEST_TIME) {

    $response_json = [];

    $response_json['status'] = FALSE;

    if ($this->manager->isActive()) {

      // If the type of activity was not specified in the query, assume we
      // are tracking a basic hit.
      $params = $_GET + [
        'type' => TetherStatsAnalytics::ACTIVITY_HIT,
      ];

      if (in_array($params['type'], TetherStatsTrackController::allowableActivityTypes())) {

        if (!empty($params['elid']) && is_numeric($params['elid'])) {

          $element = $this->manager->getStorage()->loadElement($params['elid']);

          if (!isset($element)) {

            $this->logger->error("Unable to find element '{$params['elid']}' in order to track an activity.");
          }
        }
        else {

          // If no element elid was specified, assume we have an identity set
          // and try to find an element match.
          $identity_set = new TetherStatsIdentitySet(TetherStatsIdentitySet::reduceToAllowableKeys($params));

          if ($this->manager->testValidityOfIdentitySet($identity_set)) {

            $element = $this->manager->getStorage()->createElementFromIdentitySet($identity_set);
          }
        }

        if (isset($element)) {

          if ($params['type'] == 'impression') {

            if (!empty($params['alid'])) {

              $ilid = $this->manager->getStorage()->trackImpression($element->getId(), $params['alid'], $event_time);

              if ($ilid) {

                $response_json['status'] = TRUE;
                $response_json['alid'] = $params['alid'];
                $response_json['ilid'] = $ilid;
              }
            }
            else {

              $this->logger->error("Attempted to track an impression but no alid was provided.");
            }
          }
          else {

            $ip_address = $this->requestStack->getCurrentRequest()->getClientIp();
            $referrer = isset($params['referrer']) ? $params['referrer'] : NULL;
            $uid = $this->account->isAuthenticated() ? $this->account->id() : NULL;

            $alid = $this->manager->getStorage()->trackActivity($element->getId(), $params['type'], $event_time, $ip_address, session_id(), $_SERVER['HTTP_USER_AGENT'], $referrer, $uid);

            if ($alid) {

              $response_json['status'] = TRUE;
              $response_json['alid'] = $alid;

              if ($params['type'] == TetherStatsAnalytics::ACTIVITY_HIT) {

                // See if there were any embedded impressions in the call and
                // track those too.
                $this->trackEmbeddedImpressions($params, $alid, $event_time);
              }
            }
          }
        }
        else {

          $this->logger->warning("Tracking failed. No element to track from.");
        }
      }
      else {

        $type = SafeMarkup::checkPlain($params['type']);
        $this->logger->warning("Attempted to track an activity of unknown type '{$type}'");
      }
    }

    return new JsonResponse($response_json);
  }

  /**
   * Track impressions embedded in the query.
   *
   * Impressions may be embedded in the query string using the
   * keys imp1, imp2, imp3, ...
   *
   * Each impression value is a url encoded string consisting of comma-
   * separated key=value pairs forming an identity set. This way all the
   * impressions on a page can be included with the call to track the page
   * hit event.
   *
   * @param array $params
   *   The query parameters with the impression keys.
   * @param int $alid
   *   The activity id to impress on.
   * @param int $event_time
   *   The time of the activity.
   */
  private function trackEmbeddedImpressions(array $params, $alid, $event_time) {

    for ($i = 0; isset($params['imp' . $i]); $i++) {

      $impression_data = rawurldecode($params["imp{$i}"]);
      $parts = explode(',', $impression_data);

      $identity_params = [];

      foreach ($parts as $part) {

        list($key, $value) = explode('=', $part);
        $identity_params[$key] = $value;
      }

      $identity_set = new TetherStatsIdentitySet($identity_params);

      if ($this->manager->testValidityOfIdentitySet($identity_set)) {

        $element = $this->manager->getStorage()->createElementFromIdentitySet($identity_set);
        $this->manager->getStorage()->trackImpression($element->getId(), $alid, $event_time);
      }
      else {

        $this->logger->warning("Unable to track impression on activity with alid '{$alid}'.");
      }
    }
  }

}
