<?php

namespace Drupal\atwork_mail_send_update\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\atwork_mail_send_update\AtworkMailSendUpdateDbGetSubscriptions;
use Drupal\atwork_mail_send_update\AtworkMailSendUpdateDbGetRenewals;
use Drupal\atwork_mail_send_update\GetNewSubs;


/**
 * Class AtworkMailSendUpdateController.
 */
class AtworkMailSendUpdateController extends ControllerBase {
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   * This is from scaffolding provided at
   * http://karimboudjema.com/en/drupal/20180807/create-queue-controller-drupal8.
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
  public function __construct() {
    $this->queueFactory = \Drupal::service('queue');
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
   * Kick off the queue generators.
   */
  public function main() {
    // Gets data for subs, and sets up queue.
    $this->getSubscriptionData('subscriptions', 'SubQueue', 'remove');
    // Gets data for News subs and sets up queue.
    $this->getSubscriptionData('newsletter', 'NewsletterSubQueue', 'remove');
    // Renew any subscriptions that have been removed
    // but the user is now active again.
    $this->getSubscriptionData('subscriptions', 'RenewSubQueue', 'renew');
    // Renew any Newsletter subscriptions for users
    // who have been deactivated and reactivated.
    $this->getSubscriptionData('newsletter', 'RenewNewsSubQueue', 'renew');
    // Gather new users and create a newsletter subscription for them
    $this->subNewUsers('atwork_newsletter');
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
    // Create a new DB user object.
    if ($action_type == 'remove') {
      $users = new AtworkMailSendUpdateDbGetSubscriptions($sub_type);
    }
    if ($action_type == 'renew') {
      $users = new AtworkMailSendUpdateDbGetRenewals($sub_type);
    }
    // Set up a DB call and get a list of user ID's.
    $user_array = $users->getUserIds();
    if (empty($user_array)) {
      return FALSE;
    }
    return $user_array;
  }

  // TODO: We want to make sure all active users are subscribed to Newsletter.
  // This can likely replace the "Renew" function for newsletters.
  // Information on subscribing users to simplenews newsletters
  // can be found here https://www.drupal.org/project/simplenews/issues/2947253.

  /**
   * Function that allows us to get user ids and subscribe them.
   *
   * @param string $newsletter
   *   Newsletter we want to add subscription for.
   */
  protected function subNewUsers($newsletter) {
    $gen_user_list = new GetNewSubs($newsletter);
    $subs_to_add = $gen_user_list->getUserUids();

    if (!$subs_to_add || empty($subs_to_add)) {
      \Drupal::logger('atwork_mail_send_update')->notice('No new users to subscribe to our newsletter.');
      return;
    }

    // Get the queue implementation for 'subscription_queue' queue.
    $queue = $this->queueFactory->get('NewsletterAddNewSubs');
    // Get the total of items in the queue before adding new items.
    $totalItemsBefore = $queue->numberOfItems();

    // For each element of the array, create a new queue item.
    foreach ($subs_to_add as $element) {
      // Create new queue item.
      $queue->createItem($element);
    }
    // Get the total of item in the Queue.
    $totalItemsAfter = $queue->numberOfItems();
    \Drupal::logger('atwork_mail_send_update')->notice('The Queue of new users to sign up for newsletter had @totalBefore items. We should have added @count items in the Queue. Now the Queue has @totalAfter items.',
      [
        '@totalBefore' => $totalItemsBefore,
        '@count' => count($subs_to_add),
        '@totalAfter' => $totalItemsAfter,
      ]);
  }

}
