<?php

namespace Drupal\atwork_mail_send_update;

/**
 * Class NewsletterSubscribe.
 *
 * @package Drupal\atwork_mail_send_update
 */
class NewsletterSubscribe {

  /**
   * Machine name of a newsletter.
   *
   * @var string
   *   A string that contains the machine name of the newsletter
   *   we want to sub users to.
   */
  protected $newsletterId;

  /**
   * Relevant queue object.
   *
   * @var object
   *   Object that will hold a queue object that we can add uids to
   *   in order to create new subscriptions on queue runs.
   */
  protected $queueFactory;

  /**
   * NewsletterSubscribe constructor.
   *
   * @param string $id
   *   The machine name of the newsletter issue we want to sub users to.
   */
  public function __construct($id) {
    $this->queueFactory = \Drupal::service('queue');
    $this->newsletterId = $id;
    $this->subNewUsers();
  }

  /**
   * Getter for newsletter id.
   *
   * @return string
   *   Returns the stored machine name for the simplenews newsletter issue.
   */
  private function getNewsletterId() {
    return $this->newsletterId;
  }

  /**
   * Main Function that allows us to get user ids and subscribe them.
   *
   * This function will grab all users that are active, and not part of the
   * requested newsletter issue subscription, and add them to a queue
   * so that they can be subscribed on the next cron run.
   */
  protected function subNewUsers() {
    // Machine name of the simplenews issue passed in to constructor.
    $newsletter_id = $this->getNewsletterId();
    // Class NewSubs will complete our DB query for this.
    $gen_user_list = new NewSubs($newsletter_id);
    // Get an array of UIDs for users who need to be added.
    $subs_to_add = $gen_user_list->getUserUids();
    // TODO: Currently we check if users have an email address in the
    // queue itself, perhaps we should check here and add emails as the element
    // or drop them before we add them.
    // If we don't have any uids to add, log it.
    if (!$subs_to_add || empty($subs_to_add)) {
      \Drupal::logger('atwork_mail_send_update')->notice('No new users to subscribe to our newsletter.');
      return;
    }

    // Get the proper queue.
    $queue = $this->queueFactory->get('NewsletterAddNewSubs');
    // Get the total of items in the queue before adding new items.
    $totalItemsBefore = $queue->numberOfItems();
    // For each element of the array, create a new queue item.
    foreach ($subs_to_add as $element) {
      // Create new queue item.
      $queue->createItem($element);
    }
    // Get the new total of all items in the Queue.
    $totalItemsAfter = $queue->numberOfItems();
    // Log what we have done.
    \Drupal::logger('atwork_mail_send_update')->notice('The Queue of new users to sign up for newsletter had @totalBefore items. We should have added @count items in the Queue. Now the Queue has @totalAfter items.',
      [
        '@totalBefore' => $totalItemsBefore,
        '@count' => count($subs_to_add),
        '@totalAfter' => $totalItemsAfter,
      ]);
  }

}
