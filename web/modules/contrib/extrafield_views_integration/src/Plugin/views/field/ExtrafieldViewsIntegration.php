<?php

namespace Drupal\extrafield_views_integration\Plugin\views\field;

use Drupal\extrafield_views_integration\lib\ExtrafieldRenderClassInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("extrafield_views_integration")
 */
class ExtrafieldViewsIntegration extends FieldPluginBase {
    /**
     * @{inheritdoc}
     */
    public function query() {
        // Leave empty to avoid a query on this field.
    }

    /**
     * @{inheritdoc}
     */
    public function render(ResultRow $values) {
        if (class_exists($this->definition['render_class'])) {
            /** @var ExtrafieldRenderClassInterface $class */
            $class = $this->definition['render_class'];
            return $class::render($values->_entity);
        }
        else {
            drupal_set_message(
                t(
                    'An error occurred render_class: @render_class doesn´t exists.',
                    array('@render_class' => $this->definition['render_class'])
                ),
                'warning'
            );
        }
    }
}