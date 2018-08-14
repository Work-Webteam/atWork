<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;

/**
 * Prepares vars for views unformatted rows templates.
 *
 * Default template: views-view-views-fs.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_views_view_views_fs(&$vars) {
  $view = $vars['view'];
  $rows = $vars['rows'];
  $style = $view->style_plugin;
  $options = $style->options;
  foreach ($rows as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['rows'][$id]['content'] = $row;
  }
  $configs = array(
    'controls' => $options['controls'],
    'pager' => $options['pager'],
    'dimensions' => $options['views_dimensions'],
    'fullwidth' => $options['views_fullwidth'],
    'responsive' => $options['views_responsive'],
    'increase' => $options['views_increase'],
  );
  $vars['#attached']['drupalSettings']['view_fs_fractionslider'] = $configs;
}

/**
 * Implements template_preprocess_views_view_fields().
 */
function views_fs_preprocess_views_view_fields(&$vars) {
  $view = $vars['view'];
  if ($view->style_plugin->getPluginId() == 'views_fs') {
    // Loop through the fields for this view.
    $previous_inline = FALSE;
    // Ensure it's at least an empty array.
    $vars['fields'] = [];
    /** @var \Drupal\views\ResultRow $row */
    $row = $vars['row'];
    foreach ($view->field as $id => $field) {
      // Render this even if set to exclude so it can be used elsewhere.
      $field_output = $view->style_plugin->getField($row->index, $id);
      $empty = $field->isValueEmpty($field_output, $field->options['empty_zero']);
      if (empty($field->options['exclude']) && (!$empty || (empty($field->options['hide_empty']) && empty($vars['options']['hide_empty'])))) {
        $object = new stdClass();
        $object->handler = $view->field[$id];
        $object->inline = !empty($vars['options']['inline'][$id]);
        // Set up default value of the flag that indicates whether to display a
        // colon after the label.
        $object->has_label_colon = FALSE;

        $object->element_type = $object->handler->elementType(TRUE, !$vars['options']['default_field_elements'], $object->inline);
        if ($object->element_type) {
          $attributes = [];
          if ($object->handler->options['element_default_classes']) {
            $attributes['class'][] = 'field-content';
          }

          if ($classes = $object->handler->elementClasses($row->index)) {
            $attributes['class'][] = $classes;
          }
          $object->element_attributes = new Attribute($attributes);
        }

        $object->content = $field_output;
        if (isset($view->field[$id]->field_alias) && isset($row->{$view->field[$id]->field_alias})) {
          $object->raw = $row->{$view->field[$id]->field_alias};
        }
        else {
          // Make sure it exists to reduce NOTICE.
          $object->raw = NULL;
        }

        if (!empty($vars['options']['separator']) && $previous_inline && $object->inline && $object->content) {
          $object->separator = Xss::filterAdmin($vars['options']['separator']);
        }

        $object->class = Html::cleanCssIdentifier($id);

        $previous_inline = $object->inline;
        // Set up field wrapper element.
        $object->wrapper_element = $object->handler->elementWrapperType(TRUE, TRUE);
        if ($object->wrapper_element === '' && $vars['options']['default_field_elements']) {
          $object->wrapper_element = $object->inline ? 'span' : 'div';
        }

        // Set up field wrapper attributes if field wrapper was set.
        if ($object->wrapper_element) {
          $attributes = [];
          if ($object->handler->options['element_default_classes']) {
            $attributes['class'][] = 'views-field';
            $attributes['class'][] = 'views-field-' . $object->class;
            $attributes['class'][] = 'slide-in';
            $attributes['data-in'] = $view->style_plugin->options[$id]['data-in'];
            $attributes['data-out'] = $view->style_plugin->options[$id]['data-out'];
            $attributes['data-step'] = $view->style_plugin->options[$id]['data-step'];
            $attributes['data-time'] = $view->style_plugin->options[$id]['data-time'];
            $attributes['data-ease-in'] = $view->style_plugin->options[$id]['data-ease-in'];
            $attributes['data-ease-out'] = $view->style_plugin->options[$id]['data-ease-out'];
            $attributes['data-position'] = $view->style_plugin->options[$id]['space'] . ',' . $view->style_plugin->options[$id]['lspace'];
          }

          if ($classes = $object->handler->elementWrapperClasses($row->index)) {
            $attributes['class'][] = $classes;
          }
          $object->wrapper_attributes = new Attribute($attributes);
        }

        // Set up field label.
        $object->label = $view->field[$id]->label();

        // Set up field label wrapper and its attributes.
        if ($object->label) {
          // Add a colon in a label suffix.
          if ($object->handler->options['element_label_colon']) {
            $object->label_suffix = ': ';
            $object->has_label_colon = TRUE;
          }

          // Set up label HTML element.
          $object->label_element = $object->handler->elementLabelType(TRUE, !$vars['options']['default_field_elements']);

          // Set up label attributes.
          if ($object->label_element) {
            $attributes = [];
            if ($object->handler->options['element_default_classes']) {
              $attributes['class'][] = 'views-label';
              $attributes['class'][] = 'views-label-' . $object->class;
            }

            // Set up field label.
            $element_label_class = $object->handler->elementLabelClasses($row->index);
            if ($element_label_class) {
              $attributes['class'][] = $element_label_class;
            }
            $object->label_attributes = new Attribute($attributes);
          }
        }

        $vars['fields'][$id] = $object;
      }
    }
  }
}
