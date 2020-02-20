<?php

namespace Drupal\forward\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Mail\MailFormatHelper;

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
   * Unlike PHPMail, the message is not coverted to plain text by default.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter(). If the 'params'
   *   subarray defines a 'plain_text' key with a TRUE value, the message will
   *   be converted from HTML into plain text before sending.
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    if (!empty($message['params']['plain_text'])) {
      $message['body'] = MailFormatHelper::htmlToText($message['body']);
      $message['body'] = MailFormatHelper::wrapMail($message['body']);
    }

    return $message;
  }

}
