<?php

namespace Drupal\rate\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rate\RateEntityVoteWidget;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("rate_field")
 */
class RateField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options.
   *
   * @return array
   *   Array of options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['widget_type'] = ['default' => 'fivestar'];

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['widget_type'] = [
      '#title' => $this->t('Widget type'),
      '#type' => 'select',
      '#default_value' => $this->options['widget_type'],
      '#options' => RateEntityVoteWidget::getRateWidgets(),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    $widget = [];

    if ($entity) {
      $widget = [
        '#lazy_builder' => ['rate.entity.vote_widget:buildRateVotingWidget',
          [
            $entity->id(),
            $entity->getEntityType()->id(),
            $entity->bundle(),
            $this->options['widget_type'],
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }

    return $widget;
  }

}
