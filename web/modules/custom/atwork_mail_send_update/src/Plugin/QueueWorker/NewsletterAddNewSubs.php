<?php

namespace Drupal\atwork_mail_send_update\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\simplenews\Entity\Subscriber;
use Drupal\simplenews\Form\SubscriptionsFormBase;
use Drupal\simplenews\NewsletterInterface;
use Drupal\simplenews\SubscriberInterface;
use Drupal\simplenews\Subscription\SubscriptionManager;
use Drupal\user\Entity\User;

/**
 * Save queue item in a node.
 *
 * To process the queue items whenever Cron is run,
 * we need a QueueWorker plugin with an annotation which defines
 * to which queue it applied.
 *
 * @QueueWorker(
 *   id = "NewsletterAddNewSubs",
 *   title = @Translation("Make sure any new users are subscribed to our newsletter"),
 *   cron = {"time" = 60}
 * )
 */
class NewsletterAddNewSubs extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   *
   *   $item here will be an entity id for the subscription.
   *   We can then turn off subscriptions for each entity id we have.
   */
  public function processItem($item) {
    // Take each user uid and sign them up.
    try {
      // Update subscription status by uid.
      $current_sub = User::load($item->uid);
      // Make sure this user has an actual email.
      if ($current_sub->hasField('mail')) {
        $mail = $current_sub->getEmail();
        $subscription_manager = \Drupal::service('simplenews.subscription_manager');
        // For now I am hard-coding this. In the future, we may want to
        // find this newsletter a different way (or pass it in
        // the queue variables).
        $newsletter_id = 'atwork_newsletter';

        $subscription_manager->subscribe($mail, $newsletter_id, NULL, 'website');
      }
      else {
        \Drupal::logger('atwork_mail_send_update')->notice('User @username does not have an email, not adding subscription.',
          [
            '@username' => $current_sub->getUsername(),
          ]);
      }

    }
    catch (\Exception $e) {
      \Drupal::logger('atwork_mail_send_update')->error('Exception when attempting to add user to newsletter sub: @error',
        ['@error' => $e->getMessage()]);
    }
  }

}
