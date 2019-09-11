<?php

namespace Drupal\atwork_mail_send_update\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Save queue item in a node.
 *
 * To process the queue items whenever Cron is run,
 * we need a QueueWorker plugin with an annotation which defines
 * to which queue it applied.
 *
 * @QueueWorker(
 *   id = "NewsletterSubQueue",
 *   title = @Translation("Clean up newsletter subscriptions for old users"),
 *   cron = {"time" = 60}
 * )
 */
class NewsletterSubQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   *
   *   $item here will be an entity id for the subscription.
   *   We can then turn off subscriptions for each entity id we have.
   */
  public function processItem($item) {
    print_r($item->id);
    \Drupal::logger('atwork_mail_send_update')->debug($item->id);
    // Take each uid and turn off the email option.
    try {
      // Update subscription status by entity id.
      $connection = \Drupal::database();
      $query = $connection->update('simplenews_subscriber__subscriptions')
        ->fields([
          'subscriptions_status' => 0,
        ])
        ->condition('entity_id', $item->id, '=')
        ->execute();
      if ($query) {
        // Logging to aid in debugging.
        \Drupal::logger('atwork_mail_send_update')->notice('Subscription for entity @item has been updated, this subscription will no longer be sent.',
          [
            '@item' => $item->id,
          ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('atwork_mail_send_update')->error('Exception for newsletter queue @error',
          ['@error' => $e->getMessage()]);
    }
  }

}
