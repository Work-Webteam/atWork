<?php

/**
 * @file
 * Contains user_alert.page.inc.
 *
 * Page callback for User alert entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for User alert templates.
 *
 * Default template: user_alert.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user_alert(array &$variables) {
  // Fetch UserAlert Entity Object.
  $user_alert = $variables['elements']['#user_alert'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
