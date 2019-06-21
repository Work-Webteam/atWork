<?php

namespace Drupal\forward\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;

/**
 * Defines a custom mail interface so that Forward emails can be sent as HTML.
 *
 * @Mail(
 *   id = "forward_mail",
 *   label = @Translation("Forward HTML mailer"),
 *   description = @Translation("Sends the message as HTML, using PHP's native mail() function.")
 * )
 */
class ForwardMail extends PhpMail {

  /**
   * Concatenates and wraps the email body for HTML mails.
   *
   * Unlike PHPMail, the message is not coverted to plain text.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);
    return $message;
  }

}
