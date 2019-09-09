<?php

namespace Drupal\atwork_mail_send_update\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\atwork_mail_send_update\AtworkMailSendUpdateDbGetSubscriptions;

/**
 * Class AtworkMailSendUpdateController.
 */
class AtworkMailSendUpdateController extends ControllerBase {
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   * This is from scaffolding provided at
   * http://karimboudjema.com/en/drupal/20180807/create-queue-controller-drupal8
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  /**
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $queueFactory;
  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs a new AtworkMailSendUpdateController object.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   * @var \GuzzleHttp\ClientInterface
   */
  public function __construct(MessengerInterface $messenger, QueueFactory $queue, ClientInterface $client) {
    $this->messenger = $messenger;
    $this->queueFactory = $queue;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('queue'),
      $container->get('http_client')
    );
  }

  /**
   * Delete the queue 'subscription_queue'.
   *
   * Remember that the command drupal dq checks first for a queue worker
   * and if it exists, DC supposes that a queue exists.
   */
  public function deleteSubQueue() {
    $this->queueFactory->get('SubQueue')->deleteQueue();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('The queue "SubQueue" has been deleted'),
    ];
  }

  /**
   * Deletes Newsletter subqueue.
   *
   * @return array
   *   Returns log of removed queue
   */
  public function deleteNewsletterSubQueue() {
    $this->queueFactory->get('NewsletterSubQueue')->deleteQueue();
    return [
      '#type' => 'markup',
      '#markup' => $this->t('the queue "NewsletterSubQueue" has been deleted'),
    ];
  }

  /**
   * Main function that kicks off sub work.
   */
  public function main() {
    // Gets data for subs, and sets up queue.
    $this->getSubscriptionData();
    // Gets data for News subs and sets up queue.
    $this->getNewsletterSubscriptionData();
  }

  /**
   * Get data from db source and create a item queue to process.
   *
   * @return array
   *   Return string.
   */
  public function getSubscriptionData() {
    // 1. Get data into an array of objects
    // 2. Get the queue and the total of items before the operations
    // 3. For each element of the array, create a new queue item
    // 1. Get data into an array of objects
    // This works in concert with getNewsletterSubData and
    // get userSubData.
    $data = $this->getSubData();

    if (!$data || empty($data)) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('No users require subscription updates'),
      ];
    }
    // 2. Get the queue and the total of items before the operations
    // Get the queue implementation for 'subscription_queue' queue.
    $queue = $this->queueFactory->get('subscription_queue');
    // Get the total of items in the queue before adding new items.
    $totalItemsBefore = $queue->numberOfItems();
    // 3. For each element of the array, create a new queue item.
    foreach ($data as $element) {
      // Create new queue item.
      $queue->createItem($element);
    }
    // 4. Get the total of item in the Queue.
    $totalItemsAfter = $queue->numberOfItems();
    // 5. Get what's in the queue now.
    $tableVariables = $this->getItemList($queue);
    $finalMessage = $this->t('The Subscriptions Queue had @totalBefore items. We should have added @count items in the Queue. Now the Queue has @totalAfter items.',
      [
        '@count' => count($data),
        '@totalAfter' => $totalItemsAfter,
        '@totalBefore' => $totalItemsBefore,
      ]);
    return [
      '#type' => 'table',
      '#caption' => $finalMessage,
      '#header' => $tableVariables['header'],
      '#rows' => $tableVariables['rows'],
      '#attributes' => $tableVariables['attributes'],
      '#sticky' => $tableVariables['sticky'],
      'empty' => $this->t('No items.'),
    ];
  }

  /**
   * Get data from db source and create a item queue to process.
   *
   * @return array
   *   Return string.
   */
  public function getNewsletterSubscriptionData() {
    // 1. Get data into an array of uids
    // 2. Get the queue and the total of items before the operations
    // 3. For each element of the array, create a new queue item
    // This works in concert with getNewsletterSubData and
    // getUserSubData.
    $data = $this->getNewsletterSubData();

    if (!$data || empty($data)) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('No users require newsletter subscriptions updates'),
      ];
    }
    // 2. Get the queue and the total of items before the operations
    // Get the queue implementation for 'exqueue_import' queue.
    $queue = $this->queueFactory->get('SubQueueNewsletter_import');
    // Get the total of items in the queue before adding new items.
    $totalItemsBefore = $queue->numberOfItems();
    // 3. For each element of the array, create a new queue item.
    foreach ($data as $element) {
      // Create new queue item.
      $queue->createItem($element);
    }
    // 4. Get the total of item in the Queue.
    $totalItemsAfter = $queue->numberOfItems();
    // 5. Get what's in the queue now.
    $tableVariables = $this->getItemList($queue);
    $finalMessage = $this->t('The Newsletter Queue had @totalBefore items. We should have added @count items in the Queue. Now the Queue has @totalAfter items.',
      [
        '@count' => count($data),
        '@totalAfter' => $totalItemsAfter,
        '@totalBefore' => $totalItemsBefore,
      ]);
    return [
      '#type' => 'table',
      '#caption' => $finalMessage,
      '#header' => $tableVariables['header'],
      '#rows' => $tableVariables['rows'],
      '#attributes' => $tableVariables['attributes'],
      '#sticky' => $tableVariables['sticky'],
      'empty' => $this->t('No items.'),
    ];
  }

  /**
   * Generate an array of objects from DB.
   *
   * @return array|bool
   *   Return an array or false.
   */
  protected function getSubData() {
    // Create a new DB user object.
    $users = new AtworkMailSendUpdateDbGetSubscriptions("subscriptions");
    // Set up a DB call and get a list of user ID's.
    $user_array = $users->getUserIds();
    if (empty($user_array)) {
      return FALSE;
    }
    return $user_array;
  }

  /**
   * Generate an array of objects from DB.
   *
   * @return array|bool
   *   Return an array or false.
   */
  protected function getNewsletterSubData() {
    // Create a new DB user object.
    $users = new AtworkMailSendUpdateDbGetSubscriptions("newsletter");
    // Set up a DB call and get a list of user ID's.
    $user_array = $users->getUserIds();
    if (empty($user_array)) {
      return FALSE;
    }
    return $user_array;
  }

}
