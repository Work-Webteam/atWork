<?php

namespace Drupal\atwork_mail_send_update;

/**
 * Class SubscriptionData.
 *
 * @package Drupal\atwork_mail_send_update
 */
class SubscriptionData {

  /**
   * Relevant queue object.
   *
   * @var object
   *   Object that will hold a queue object that we can add uids to
   *   in order to create new subscriptions on queue runs.
   */
  protected $queueFactory;

  /**
   * SubscriptionData constructor.
   *
   * @param $sub_type
   * @param $queue_type
   * @param $action_type
   */
  public function __construct($sub_type, $queue_type, $action_type) {
    $this->getSubscriptionData($sub_type, $queue_type, $action_type);
    $this->queueFactory = \Drupal::service('queue');
  }

  /**
   * Main function to set up queues and gather ids.
   *
   * @param string $sub_type
   *   The type of subscription we are working on (subscriptions, newsletter).
   * @param string $queue_type
   *   The queue we will be adding data too.
   * @param string $action_type
   *   The action we will be using (renew, remove)
   */
  public function getSubscriptionData($sub_type, $queue_type, $action_type) {
    // 1. Get data into an array of objects
    // 2. Get the queue and the total of items before the operations
    // 3. For each element of the array, create a new queue item
    // 1. Get data into an array of objects
    // This works in concert with getNewsletterSubData and
    // get userSubData.
    $data = $this->getSubData($sub_type, $action_type);
    $queue = $this->queueFactory->get($queue_type);

    if (!$data || empty($data)) {
      \Drupal::logger('atwork_mail_send_update')->notice('No users require @sub_type @action_type updates, nothing added to the @queue_type queue', [
        '@sub_type' => $sub_type,
        '@queue_type' => $queue_type,
        '@action_type' => $action_type,
      ]);
      return;
    }

    // 2. Get the queue and the total of items before the operations
    // Get the queue implementation for 'subscription_queue' queue.
    $queue = $this->queueFactory->get($queue_type);

    // Get the total of items in the queue before adding new items.
    $totalItemsBefore = $queue->numberOfItems();

    // 3. For each element of the array, create a new queue item.
    foreach ($data as $element) {
      // Create new queue item.
      $queue->createItem($element);
    }
    // 4. Get the total of item in the Queue.
    $totalItemsAfter = $queue->numberOfItems();

    \Drupal::logger('atwork_mail_send_update')->notice('The @queue_type Queue had @totalBefore items. We should have added @count @sub_type items in the @action_type Queue. Now the Queue has @totalAfter items.',
      [
        '@count' => count($data),
        '@totalAfter' => $totalItemsAfter,
        '@totalBefore' => $totalItemsBefore,
        '@sub_type' => $sub_type,
        '@queue_type' => $queue_type,
        '@action_type' => $action_type,
      ]);
  }

  /**
   * Helper function to gather ids for processing.
   *
   * @param string $sub_type
   *   The sub type we are working on (subscriptions/Newsletter)
   * @param string $action_type
   *   The action we will be taking (renew / remove)
   *
   * @return array|bool
   *   Return the array of ids to use, or FALSE if we don't get any.
   */
  protected function getSubData($sub_type, $action_type) {
    $users = NULL;
    // Create a new DB user object.
    if ($action_type == 'remove') {
      $users = new Subscriptions($sub_type);
    }
    if ($action_type == 'renew') {
      $users = new Renewals($sub_type);
    }
    // Set up a DB call and get a list of user ID's.
    $user_array = $users->getUserIds();
    if (empty($user_array)) {
      return FALSE;
    }
    return $user_array;
  }

}
