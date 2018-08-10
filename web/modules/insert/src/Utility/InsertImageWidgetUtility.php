<?php

namespace Drupal\insert\Utility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestHelper;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class InsertImageWidgetUtility extends InsertFileWidgetUtility {

  /**
   * @inheritdoc
   */
  protected static $insert_fields = [
    'alt' => 'input[name$="[alt]"], textarea[name$="[alt]"]',
    'title' => 'input[name$="[title]"], textarea[name$="[title]"]',
    'description' => 'input[name$="[description]"], textarea[name$="[description]"]',
  ];

  /**
   * @inheritdoc
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
        'insert_styles' => ['image', 'link'],
        'insert_default' => 'image',
        'insert_width' => '',
        'insert_rotate' => FALSE,
      ];
  }

  /**
   * @inheritdoc
   */
  public function settingsForm($element, $settings) {
    $element = parent::settingsForm($element, $settings);

    $element['insert_width'] = [
      '#title' => $this->t('Maximum image insert width'),
      '#type' => 'textfield',
      '#size' => 10,
      '#field_suffix' => ' ' . t('pixels'),
      '#default_value' => $settings['insert_width'],
      '#description' => $this->t('When inserting images, the height and width of images may be scaled down to fit within the specified width. Note that this does not resize the image, it only affects the HTML output. To resize images it is recommended to install the <a href="http://drupal.org/project/image_resize_filter">Image Resize Filter</a> module.'),
      '#weight' => 26,
    ];

    $element['insert_rotate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rotation controls'),
      '#default_value' => $settings['insert_rotate'],
      '#description' => $this->t('The image may be rotated by using rotation controls.'),
      '#weight' => 27,
    ];

    return $element;
  }

  /**
   * @inheritdoc
   */
  protected function stylesListToOptions($stylesList) {
    foreach ($stylesList as $styleName => $style) {
      /* @var ImageStyle|array $style */
      $stylesList[$styleName] = is_array($style)
        ? $style['label']
        : $style->label();
    }
    return $stylesList;
  }

  /**
   * @inheritdoc
   */
  public function formElement($element, $settings) {
    $element = parent::formElement($element, $settings);
    $element['#insert_width'] = $settings['insert_width'];
    $element['#insert_rotate'] = $settings['insert_rotate'];
    return $element;
  }

  /**
   * @inheritdoc
   */
  public function process($element, FormStateInterface $form_state) {
    $element = parent::process($element, $form_state);

    if ($element === null) {
      return null;
    }

    $element['insert']['button']['#insert_rotate'] = !!$element['#insert_rotate'];

    return $element;
  }

  /**
   * @inheritdoc
   *
   * @param array $styleSetting
   * @param string $defaultStyleName
   * @return array
   *   The styles to consider for inserting items. The array may contain plain
   *   arrays for pseudo-styles as well as ImageStyle objects.
   */
  public function retrieveInsertStyles($styleSetting, $defaultStyleName) {
    $allStyles = $this->retrieveStyles();

    // When the value is <all>, even styles that have been created since the
    // widget settings have been altered the last time shall be enabled;
    // Consequently, all styles have to be retrieved instead of using any actual
    // setting value.
    $selectedStyles = !empty($styleSetting['<all>'])
      ? array_combine($allStyles, $allStyles)
      // Else, filter out styles disabled per widget setting.
      : array_filter((array)$styleSetting);

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
   * @inheritdoc
   *
   * @return array
   *   Array with ImageStyle objects for the actual implemented styles and plain
   *   arrays for the pseudo styles.
   */
  public function retrieveStyles() {
    $stylesList = [];
    foreach (ImageStyle::loadMultiple() as $style) {
      /* @var ImageStyle $style */
      $stylesList[$style->getName()] = $style;
    }

    $stylesList += parent::retrieveStyles();

    $stylesList['image'] = [
      'label' => t('Original image'),
      'weight' => -10
    ];

    return $stylesList;
  }

  /**
   * @inheritdoc
   *
   * @param array|ImageStyle $style
   * @return string
   */
  public function getStyleLabel($style) {
    return is_array($style) ? $style['label'] : $style->label();
  }

  /**
   * @inheritdoc
   *
   * @param $styles
   * @return array
   */
  protected function getStyleClasses($styles) {
    $styleClasses = [];
    foreach ($styles as $styleName => $style) {
      if ($style instanceof ImageStyle) {
        $styleClasses[] = 'image-' . $styleName;
      }
    }
    return $styleClasses;
  }

  /**
   * @inheritdoc
   */
  public function render($styleName, $vars) {
    if ($styleName == 'icon_link') {
      $rendered = \Drupal::theme()->render(['insert_icon_link'], $vars);
    }
    elseif ($styleName === 'link') {
      $rendered = \Drupal::theme()->render(['insert_link'], $vars);
    }
    else {
      $rendered = \Drupal::theme()->render(
        [
          'insert_image__' . str_replace('-', '_', $styleName),
          'insert_image'
        ],
        $vars
      );
    }

    return gettype($rendered) === 'string'
      ? $rendered
      : $rendered->jsonSerialize();
  }

  /**
   * @inheritdoc
   *
   * @param string $styleName
   * @param array $element
   * @param string $fieldType
   * @return array
   */
  protected function aggregateVariables($styleName, $element, $fieldType) {
    $vars = parent::aggregateVariables($styleName, $element, $fieldType);
    $vars['url_original'] = $vars['url'];
    $vars['link_to_original'] = $this->hasInsertLinkedEffect($styleName);

    $style = ImageStyle::load($styleName);

    if ($style !== null) {
      /** @var File $file */
      $file = $vars['file'];
      $uri = $style->buildUri($file->getFileUri());
      $style->createDerivative($file->getFileUri(), $uri);
      $vars['url'] = $this->aggregateUrl($uri, !!$element['#insert_absolute']);
      $vars['uuid'] = 'insert-' . $styleName . '-' . $vars['uuid'];
    }

    $vars['insert_absolute'] = !!$element['#insert_absolute'];

    return $vars;
  }

  /**
   * Checks whether an image style has the effect that images shall link to
   * their originals when inserting using the Insert button.
   *
   * @param string $styleName
   * @return bool
   */
  protected function hasInsertLinkedEffect($styleName) {
    $style = ImageStyle::load($styleName);

    if ($style === null) {
      return FALSE;
    }

    foreach ($style->getEffects()->getConfiguration() as $effect) {
      if ($effect['id'] === 'insert_image_linked') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * @inheritdoc
   * This method is modeled after \Drupal\image\Entity\ImageStyle::buildUrl, but
   * with the modification that it will consistently use absolute or relative
   * URLs, depending on the Insert setting.
   */
  protected function aggregateUrl($uri, $absolute, $clean_urls = NULL) {
    if ($clean_urls === NULL) {
      // Assume clean URLs unless the request tells us otherwise.
      $clean_urls = TRUE;
      try {
        $request = \Drupal::request();
        $clean_urls = RequestHelper::isCleanUrl($request);
      } catch (ServiceNotFoundException $e) {
      }
    }

    // If not using clean URLs, the image derivative callback is only available
    // with the script path. If the file does not exist, use Url::fromUri() to
    // ensure that it is included. Once the file exists it's fine to fall back
    // to the actual file path, this avoids bootstrapping PHP once the files are
    // built. See \Drupal\image\Entity\ImageStyle::buildUrl.
    if (
      $clean_urls === FALSE
      && \Drupal::service('file_system')->uriScheme($uri) == 'public'
      && !file_exists($uri)
    ) {
      $directory_path = \Drupal::service('stream_wrapper_manager')
        ->getViaUri($uri)
        ->getDirectoryPath();

      $url = Url::fromUri(
        'base:' . $directory_path . '/' . file_uri_target($uri),
        ['absolute' => TRUE]
      )->toString();
    }
    else {
      $url = file_create_url($uri);
    }

    if (!$absolute && strpos($url, $GLOBALS['base_url']) === 0) {
      $url = base_path() . ltrim(str_replace($GLOBALS['base_url'], '', $url), '/');
    }

    return $url;
  }

}