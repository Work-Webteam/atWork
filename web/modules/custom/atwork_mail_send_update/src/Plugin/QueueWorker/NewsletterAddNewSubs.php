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
   *   $item here will be an user id (uid) number.
   *   We can then add subscriptions for each uid we have.
   */
  public function processItem($item) {
    // Take each user uid and sign them up.
    try {
      \Drupal::logger('atwork_mail_send_update')->debug($item);
      // Update subscription status by uid.
      $current_sub = User::load($item->uid);
      // Make sure this user has an actual email.
      if ($current_sub->hasField('mail') && !empty($current_sub->getEmail)) {
        $mail = $current_sub->getEmail();
        $subscription_manager = \Drupal::service('simplenews.subscription_manager');
        // For now I am hard-coding this. In the future, we may want to
        // find this newsletter a different way (or pass it in
        // the queue variables).
        $newsletter_id = 'atwork_newsletter';

        $subscription_manager->subscribe($mail, $newsletter_id, NULL, 'website');
        Drupal::logger('atwork_mail_send_update')->notice('User @username has been subscribed to newsletter',
          [
            '@username' => $current_sub->getUsername(),
          ]);
      }
      else {
        \Drupal::logger('atwork_mail_send_update')->notice('User @username does not have an email, not adding subscription. Subscription item is @item',
          [
            '@username' => $current_sub->getUsername(),
            '@item' => $item,
          ]);
      }

    }
    catch (\Exception $e) {
      \Drupal::logger('atwork_mail_send_update')->error('Exception when attempting to add user to newsletter sub: @error',
        ['@error' => $e->getMessage()]);
    }
  }

}
