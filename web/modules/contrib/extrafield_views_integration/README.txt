-- SUMMARY --

The Extrafield Views Integration Module enables all Drupal core extra fields in
the system from type "display" as fields in views.

-- REQUIREMENTS --

Views
Entity

 -- INSTALLATION --

For installing the module, just download the source code and enable the module.
That's all.

-- CONFIGURATION --

The module itself needs no configuration, because the extra fields that you
want to use need an render_class key and a class implements ExtrafieldRenderClassInterface.
Every module can use hook_entity_extra_field_info to register the extra fields. Normally it looks like
this:

function hook_entity_extra_field_info() {
  $extra_fields = array(
    'entity_type' => array(
      'bundle' => array(
        'display' => array(
           'field_name' => array(
            'label' => 'field label',
            'description' => 'field description',
            'weight' => 0,
            'visible' => false,
            'render_class' => 'full\qualified\namespace\RenderClass',
          ),
        ),
      ),
   ),
);

return $extra_fields;
}

The class simply look like this:

<?php

namespace Drupal\your_module;

use Drupal\extrafield_views_integration\lib\ExtrafieldRenderClassInterface;
use Drupal\Core\Entity\Entity;

/**
 * Created by PhpStorm.
 * User: dasricardo
 * Date: 01.04.16
 * Time: 19:14
 */
class YourClass implements ExtrafieldRenderClassInterface
{
    public static function render(Entity $entity)
    {
        return "String";
    }
}

The module needs both, the render_class key and the existing class defined
in the render_class key. The module only registers extra fields from type
"display" with the required key and the existing class. Views then
passes the entity to the static render function of the field.

-- FAQ --

Q:  I register an extra field for node type "article". But views doesn't show
    me the extra field.

A:  Ensure that you add the render_class key to the extra field array and that the
    class you defined in the render_class key exists.

Q:  I see my field for the node type "article" but I don't see any output.

A:  Ensure that your render function returns a value.
