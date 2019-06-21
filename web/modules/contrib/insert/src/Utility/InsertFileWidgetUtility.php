<?php

namespace Drupal\insert\Utility;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class InsertFileWidgetUtility {

  use StringTranslationTrait;

  /**
   * Input fields that are to be mapped to placeholders when processing the
   * Insert action in JavaScript.
   *
   * @var array
   */
  protected static $insert_fields = [
    'description' => 'input[name$="[description]"]',
  ];

  /**
   * @see \Drupal\Core\Field\PluginSettingsInterface::defaultSettings()
   *
   * @return array
   */
  public static function defaultSettings() {
    return [
      'insert_absolute' => FALSE,
      'insert_styles' => ['icon_link', 'link'],
      'insert_default' => 'link',
    ];
  }

  /**
   * @see \Drupal\Core\Field\WidgetInterface::settingsForm()
   *
   * @param array $element
   * @param array $settings
   * @return array
   */
  public function settingsForm($element, $settings) {
    $stylesList = $this->retrieveStyles();
    $stylesList = $this->stylesListToOptions($stylesList);

    $element['insert_absolute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use absolute paths'),
      '#default_value' => $settings['insert_absolute'],
      '#description' => $this->t('Includes the full URL prefix "@base_url" in all links and image tags.', ['@base_url' => $GLOBALS['base_url']]),
      '#weight' => 21,
    ];

    $element['insert_styles_styles_heading'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Select which styles should be available for inserting images into text areas. If no styles are selected, the option to use a style is not displayed; If only one style is selected, that one is used automatically when inserting. If all styles are selected, new styles will be enabled by default.'),
      '#weight' => 22,
    ];

    $element['insert_styles'] = [
      '#type' => 'table',
      '#default_value' => !empty($settings['insert_styles']['<all>'])
        ? array_keys($stylesList)
        : $settings['insert_styles'],
      '#element_validate' => [[get_called_class(), 'validateStyles']],
      '#weight' => 23,
      '#tableselect' => TRUE,
      '#header' => [t('Select all')],
    ];

    foreach ($stylesList as $key => $label) {
      $element['insert_styles'][$key][$key] = [
        '#type' => 'markup',
        '#markup' => $label,
      ];
    }

    $element['insert_default'] = [
      '#title' => $this->t('Default insert style'),
      '#type' => 'select',
      '#options' => $stylesList,
      '#default_value' => $settings['insert_default'],
      '#description' => $this->t('Select the default style which will be selected by default or used if no specific styles above are enabled.'),
      '#weight' => 24,
    ];

    return $element;
  }

  /**
   * @param array $stylesList
   * @return array
   */
  protected function stylesListToOptions($stylesList) {
    foreach ($stylesList as $styleName => $style) {
      $stylesList[$styleName] = $style['label'];
    }
    return $stylesList;
  }

  /**
   * An #element_validate function for the styles list on the settings form.
   * Since when all styles are activated new styles should be enabled by
   * default, the setting value needs to be changed to be able to detect that
   * all styles were enabled when setting the styles the last time.
   * @param array $element
   * @param \Drupal\Core\Form\FormState $form_state
   */
  public static function validateStyles($element, &$form_state) {
    if (array_key_exists('#options', $element)
      && array_values($element['#value']) == array_keys($element['#options'])
    ) {
      $form_state->setValue('<all>', '<all>');
    }
  }

  /**
   * Cleans the class input removing any redundant whitespace.
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormState $form_state
   */
  public static function validateClass($element, &$form_state) {
    $trimmedClasses = [];
    foreach (explode(' ', $element['#value']) as $class) {
      $class = trim($class);
      if ($class !== '') {
        $trimmedClasses[] = $class;
      }
    }
    $form_state->setValueForElement($element, join(' ', $trimmedClasses));
  }

  /**
   * Make settings available to the process() method.
   * @see \Drupal\Core\Field\WidgetInterface::formElement()
   *
   * @param array $element
   * @param array $settings
   * @return array
   */
  public function formElement($element, $settings) {
    $element['#insert_absolute'] = $settings['insert_absolute'];
    $element['#insert_styles'] = $settings['insert_styles'];
    $element['#insert_default'] = $settings['insert_default'];
    return $element;
  }

  /**
   * Form API callback: Processes a file field element.
   * @see \Drupal\file\Plugin\Field\FieldWidget\FileWidget::process()
   *
   * @param array $element
   * @param FormStateInterface $form_state
   * @return array
   *   Element to render.
   */
  public function process($element, FormStateInterface $form_state) {

    // Prevent displaying the Insert button when the image is empty ("Add a new
    //file" row).
    if (count($element['fids']['#value']) === 0) {
      return null;
    }

    $fieldDefinitions = $this->getFieldDefinitions($element, $form_state);

    if ($fieldDefinitions === null) {
      drupal_set_message($this->t('Unable to retrieve field definitions.'), 'error');
      return null;
    }

    $item = $element['#value'];

    if (!isset($item['fids']) || count($item['fids']) === 0) {
      return null;
    }

    $file = File::load($item['fids'][0]);

    $defaultStyleName = !empty($element['#insert_default'])
      ? $element['#insert_default']
      : null;

    $insertStyles = $this->retrieveInsertStyles($element['#insert_styles'], $defaultStyleName);

    $fieldType = $fieldDefinitions[$element['#field_name']]->getType();

    $styleOptions = [];

    $element['insert'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'insert',
          'form-item',
          'container-inline',
          'inline',
        ],
        'data-insert-type' => $fieldType,
        'data-uuid' => $file->uuid(),
      ],
    ];

    $element['insert']['insert_templates'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['insert-templates']],
    ];

    foreach ($insertStyles as $styleName => $style) {
      $vars = static::aggregateVariables($styleName, $element, $fieldType);

      $element['insert']['insert_templates'][$styleName] = [
        '#type' => 'hidden',
        '#value' => count($item['fids']) === 0 ? '' : $this->render($styleName, $vars),
        '#id' => $element['#id'] . '-insert-template-' . str_replace('_', '-', $styleName),
        '#name' => $element['#name'] . '[insert_template][' . $styleName . ']',
        '#attributes' => ['class' => ['insert-template']],
      ];

      $styleOptions[$styleName] = $this->getStyleLabel($style);
    }

    $element['insert']['insert_filename'] = [
      '#type' => 'hidden',
      '#value' => $file->getFilename(),
      '#id' => $element['#id'] . '-insert-filename',
      '#name' => $element['#name'] . '[insert_filename]',
      '#attributes' => ['class' => ['insert-filename']],
    ];

    $element = $this->attachJavaScript(
      $element,
      $fieldType,
      $this->getStyleClasses($insertStyles)
    );

    $node = $form_state->getFormObject()->getEntity();

    $element['insert']['button'] = [
      '#theme' => 'insert_button_widget',
      '#type' => 'markup',
      '#options' => $styleOptions,
      '#widget' => ['type' => $fieldType],
      '#weight' => 5,
      '#fid' => $item['fids'][0],
      '#nid' => $node instanceof Node ? $node->id() : '',
      '#insert_absolute' => !!$element['#insert_absolute'],
    ];

    if ($defaultStyleName !== null) {
      $element['insert']['button']['#default_value'] = $defaultStyleName;
    }

    return $element;
  }

  /**
   * @param array $element
   * @param FormStateInterface $form_state
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]|null
   */
  protected function getFieldDefinitions($element, $form_state) {
    $bundle = FALSE;
    $formObject = $form_state->getFormObject();
    if ($formObject instanceof ContentEntityForm) {
      $bundle = $formObject->getEntity()->bundle();
    }

    if (!$bundle) {
      return null;
    }

    /** @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');

    return $entityFieldManager->getFieldDefinitions($element['#entity_type'], $bundle);
  }

  /**
   * @param array $element
   * @param string $fieldType
   * @param array $styleClasses
   * @return array
   */
  protected function attachJavaScript($element, $fieldType, $styleClasses) {
    $config = \Drupal::config('insert.config');

    $element['#attached']['drupalSettings']['insert'] = [
      'fileDirectoryPath' => file_default_scheme(),
      // These CSS classes will be retained from being dumped by CKEditor when
      // applying CKEditor styles using CKEditor's style drop-down.
      'classes' => [
        $fieldType => [
          'insertClass' => $config->get('css_classes.' . $fieldType),
          'styleClass' => join(' ', $styleClasses),
        ]
      ],
    ];
    $element['#attached']['library'][] = 'insert/insert';

    $insertSettings = [
      'fields' => static::$insert_fields,
    ];
    $element['#attached']['drupalSettings']['insert']['widgets']
      = [$fieldType => $insertSettings];

    return $element;
  }

  /**
   * @param array $styleSetting
   * @param string $defaultStyleName
   * @return array
   *   The styles to consider for inserting items.
   */
  protected function retrieveInsertStyles($styleSetting, $defaultStyleName) {
    $allStyles = $this->retrieveStyles();

    // Filter out styles disabled per widget setting.
    $selectedStyles = array_filter((array)$styleSetting);

    // Ensure default style is available.
    if ($defaultStyleName !== null && !array_key_exists($defaultStyleName, $selectedStyles)) {
      $selectedStyles[$defaultStyleName] = $allStyles[$defaultStyleName];
    }

    // Ensure only styles that are still installed are considered.
    $selectedAndInstalled = [];

    foreach (array_keys($selectedStyles) as $styleName) {
      if (array_key_exists($styleName, $allStyles)) {
        $selectedAndInstalled[$styleName] = $allStyles[$styleName];
      }
    }

    return $selectedAndInstalled;
  }

  /**
   * @return array
   */
  protected function retrieveStyles() {
    $stylesList = [];

    $stylesList['link'] = [
      'label' => t('Link to file'),
      'weight' => -12
    ];
    $stylesList['icon_link'] = [
      'label' => t('Link to file (with icon)'),
      'weight' => -11
    ];

    return $stylesList;
  }

  /**
   * @param array $style
   * @return string
   */
  protected function getStyleLabel($style) {
    return $style['label'];
  }

  /**
   * Returns any CSS classes deriving from the style definitions.
   *
   * @param $styles
   * @return array
   */
  protected function getStyleClasses($styles) {
    return [];
  }

  /**
   * Returns the rendered template for a specific style or pseudo-style.
   *
   * @param string $styleName
   * @param array $vars
   * @return string
   */
  protected function render($styleName, $vars) {
    if ($styleName == 'icon_link') {
      $rendered = \Drupal::theme()->render(['insert_icon_link'], $vars);
    }
    elseif ($styleName === 'link') {
      $rendered = \Drupal::theme()->render(['insert_link'], $vars);
    }
    else {
      return '';
    }

    return gettype($rendered) === 'string'
      ? $rendered
      : $rendered->jsonSerialize();
  }

  /**
   * @param string $styleName
   * @param array $element
   * @param string $fieldType
   * @return array
   */
  protected function aggregateVariables($styleName, $element, $fieldType) {
    if (count($element['#value']['fids']) === 0) {
      return [];
    }

    $config = \Drupal::config('insert.config');
    $file = File::load($element['#value']['fids'][0]);

    $absolute = isset($element['#insert_absolute'])
      ? !!$element['#insert_absolute']
      : FALSE;

    $vars = [
      'item' => $element['#value'],
      'style_name' => $styleName,
      'element' => $element,
      'field_type' => $fieldType,
      'file' => $file,
      'entity_type' => $file->getEntityTypeId(),
      'uuid' => $file->uuid(),
      'class' => $config->get('css_classes.' . $fieldType),
      'url' => static::aggregateUrl($file->getFileUri(), $absolute),
    ];

    return $vars;
  }

  /**
   * @param string $uri
   * @param boolean $absolute
   * @param boolean [$clean_urls]
   * @return string
   */
  protected function aggregateUrl($uri, $absolute) {
    $url = file_create_url($uri);

    if (!$absolute && strpos($url, $GLOBALS['base_url']) === 0) {
      $url = base_path() . ltrim(str_replace($GLOBALS['base_url'], '', $url), '/');
    }

    return $url;
  }

}