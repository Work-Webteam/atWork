<?php

namespace Drupal\atwork_mail_send_update\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\atwork_mail_send_update\SubscriptionData;
use Drupal\atwork_mail_send_update\NewsletterSubscribe;

/**
 * Class AtworkMailSendUpdateController.
 */
class AtworkMailSendUpdateController extends ControllerBase {

  /**
   * Kick off the queue generators.
   */
  public function main() {
    // Instantiate data for subs, and sets up queue.
    new SubscriptionData('subscriptions', 'SubQueue', 'remove');
    // Instantiate data for News subs and set up queue.
    new SubscriptionData('newsletter', 'NewsletterSubQueue', 'remove');
    // Instantiate class to renew any subscriptions that have been removed
    // but the user is now active again.
    new SubscriptionData('subscriptions', 'RenewSubQueue', 'renew');
    // Instantiate class to renew any Newsletter subscriptions for users
    // who have been deactivated and reactivated.
    new SubscriptionData('newsletter', 'RenewNewsSubQueue', 'renew');
    // Instantiate class to gather new users and create a
    // newsletter subscription for them.
    new NewsletterSubscribe('atwork_newsletter');
  }

}
