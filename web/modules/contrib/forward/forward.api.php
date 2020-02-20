<?php

/**
 * @file
 * Hooks provided by the Forward module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Add tokens before replacements are made within a Forward email.
 *
 * A module implementing this hook must also have token processing
 * defined in its my_module.tokens.inc file, otherwise the tokens added
 * in this hook will never be replaced.
 *
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   A form_state being processed.  This parameter may be null.
 *
 * @return array
 *   A token array.
 *
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Utility%21token.api.php/8
 */
function hook_forward_token(FormStateInterface $form_state) {
  return ['my_module' => ['my_token' => 'my_value']];
}

/**
 * Alter the message body before it is rendered.
 *
 * @param array $render_array
 *   The render array to alter.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   A form_state being processed.  Alterable.
 */
function hook_forward_mail_pre_render_alter(array &$render_array, FormStateInterface &$form_state) {
  $render_array['#my_module'] = ['#markup' => 'my_data'];
}

/**
 * Alter the message body after it is rendered.
 *
 * @param string $message_body
 *   The message content to alter.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   A form_state being processed.  Alterable.
 */
function hook_forward_mail_post_render_alter(&$message_body, FormStateInterface &$form_state) {
  $message_body .= '<div>This is some extra content.</div>';
}

/**
 * Post process the forward.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account of the person who forwarded.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity that was forwarded.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   A form_state being processed.
 */
function hook_forward_entity(UserInterface $account, EntityInterface $entity, FormStateInterface $form_state) {
  // Example: redirect to the home page.
  $form_state->setRedirect('<front>');
}

/**
 * @} End of "addtogroup hooks".
 */
