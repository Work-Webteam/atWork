<?php

namespace Drupal\atwork_mail_send_update\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;

/**
 * Save queue item in a node.
 *
 * To process the queue items whenever Cron is run,
 * we need a QueueWorker plugin with an annotation which defines
 * to which queue it applied.
 *
 * @QueueWorker(
 *   id = "RenewSubQueue",
 *   title = @Translation("Clean up subscriptions for old users"),
 *   cron = {"time" = 60}
 * )
 */
class RenewSubQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    // Take each uid and turn off the email option.
    try {
      // Load user, turn off subscription, save user.
      $user_sub = User::load($item->uid);
      if ($user_sub) {
        $user_sub->set('message_subscribe_email', 1);
        // Check if user is valid.
        $violations = $user_sub->validate();
        if (count($violations) === 0) {
          // If they are valid, then save the user.
          $user_sub->save();
          // Log in the watchdog for debugging purpose.
          \Drupal::logger('atwork_mail_send_update')->notice('Renewed subscriptions for ' . $user_sub->get('name')->getString());
        }
        else {
          \Drupal::logger('atwork_mail_send_update')->warning('Cannot renew subscriptions for ' . $user_sub->get('name')->getString() . ' because of the following violations: ' . $violations);
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('atwork_mail_send_update')->warning('Exception for subscription renewal queue @error',
        ['@error' => $e->getMessage()]);
    }
  }

}
