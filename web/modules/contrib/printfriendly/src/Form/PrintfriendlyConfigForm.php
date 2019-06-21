<?php

namespace Drupal\printfriendly\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Declare PrintfriendlyConfigForm.
 */
class PrintfriendlyConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'printfriendly_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('printfriendly.settings');

    $form = parent::buildForm($form, $form_state);

    $form['printfriendly_notification'] = [
      '#markup' => '<div>
        <h2>Does your website use these technologies?</h2>
        <ul>
            <li>- Password protected websites (paywall or intranet)</li>
            <li>- JavaScript to display content (Angular/React applications)</li>
        </ul>
        <p>If yes, you need to <a href="https://www.printfriendly.com/button/pro">purchase a PrintFriendly Pro subscription</a> for the module to work properly on your website (<a href="http://blog.printfriendly.com/2017/11/printfriendly-pdf-plugin-is-changing.html">learn why</a>).</p>
        <p>If you are an existing Pro customer, no further action is required</p>
      </div>',
    ];

    $display_options = node_type_get_names() + ['teaser' => t('Teaser')];
    $form['printfriendly_display'] = [
      '#type' => 'checkboxes',
      '#title' => t('Display Print Friendly Button On:'),
      '#options' => $display_options,
      '#default_value' => !empty($config->get('printfriendly_display')) ? array_filter($config->get('printfriendly_display')) : [],
    ];

    $form['printfriendly_button_type'] = [
      '#type' => 'fieldset',
      '#title' => t('Choose button'),
    ];

    $pf_btn_groups = [

      /* Button Group 1 */
      'btn_group_1' => [
        'printfriendly-pdf-email-button.png',
        'printfriendly-pdf-email-button-md.png',
        'printfriendly-pdf-email-button-notext.png',
      ],

      /* Button Group 2 */
      'btn_group_2' => [
        'printfriendly-pdf-button-nobg.png',
        'printfriendly-pdf-button.png',
        'printfriendly-pdf-button-nobg-md.png',
      ],

      /* Button Group 3 */
      'btn_group_3' => [
        'printfriendly-button-nobg.png',
        'printfriendly-button.png',
        'printfriendly-button-md.png',
        'printfriendly-button-lg.png',
      ],

      /* Button Group 4 */
      'btn_group_4' => [
        'print-button.png',
        'print-button-nobg.png',
        'print-button-gray.png',
      ],

      /* Button Group Custom */
      'btn_group_custom' => [
        'custom-button-img-url',
      ],

    ];

    $default_print_icon = '';
    if ($config->get('printfriendly_image') !== NULL) {
      $default_print_icon = $config->get('printfriendly_image');
    }
    else {
      $default_print_icon = 'print-button.png';
    }

    $img_path = drupal_get_path('module', 'printfriendly') . '/images';
    foreach ($pf_btn_groups as $btn_group_name => $btn_group) {

      if ($btn_group_name != 'btn_group_custom') {
        $form['printfriendly_button_type'][$btn_group_name]['#prefix'] = '<div class="pf-btn-group ' . $btn_group_name . '">';
        $form['printfriendly_button_type'][$btn_group_name]['#suffix'] = '</div>';

        foreach ($btn_group as $btn_group_image) {
          $checked = FALSE;
          if ($default_print_icon == $btn_group_image) {
            $checked = TRUE;
          }

          $image_full_path = '<img src="' . file_create_url($img_path . '/' . $btn_group_image) . '" />';
          $form['printfriendly_button_type'][$btn_group_name][$btn_group_image]['printfriendly_image'] = [
            '#type' => 'radio',
            '#title' => $image_full_path,
            '#return_value' => $btn_group_image,
            '#attributes' => ['name' => ['printfriendly_image'], 'checked' => $checked],
          ];
        }
      }
      else {
        $checked = FALSE;
        if ($default_print_icon == 'custom-button-img-url') {
          $checked = TRUE;
        }

        $form['printfriendly_button_type'][$btn_group_name]['custom-button-img-url']['printfriendly_image'] = [
          '#type' => 'radio',
          '#title' => t('<b>Custom Image</b>'),
          '#return_value' => 'custom-button-img-url',
          '#description' => t('Enter the full URL to the image http://devt.drupalchamp.org/sites/default/files/logo.png'),
          '#prefix' => '<div class="pf-btn-group ' . $btn_group_name . ' clear">',
          '#attributes' => ['name' => ['printfriendly_image'], 'checked' => $checked],
        ];

        $form['printfriendly_button_type']['custom_button_img_url'] = [
          '#type' => 'textfield',
          '#default_value' => $config->get('custom_button_img_url', ''),
          '#suffix' => '</div>',
        ];
      }
    }

    /**
     * Add more features here.
     */
    $form['printfriendly_features'] = [
      '#type' => 'fieldset',
      '#title' => t('Features'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['printfriendly_features']['printfriendly_page_header'] = [
      '#type' => 'select',
      '#title' => t('Page header'),
      '#options' => [
        'default_logo' => 'My Website Icon',
        'custom_logo' => 'Upload an Image',
      ],
      '#default_value' => $config->get('printfriendly_page_header', 'default_logo'),
    ];

    $form['printfriendly_features']['printfriendly_page_custom_header'] = [
      '#type' => 'textfield',
      '#title' => t('Enter URL'),
      '#description' => t('Put full path of the file like http://devt.drupalchamp.org/sites/default/files/drupal-logo.png'),
      '#states' => [
        'invisible' => [
          ':input[name="printfriendly_page_header"]' => [
            ['value' => t('default_logo')],
          ],
        ],
      ],
      '#default_value' => $config->get('printfriendly_page_custom_header', ''),
    ];

    $form['printfriendly_features']['printfriendly_tagline'] = [
      '#type' => 'textfield',
      '#title' => t('Header tagline'),
      '#default_value' => $config->get('printfriendly_tagline', ''),
      '#description' => t('Add a specific tagline to the header.'),
      '#states' => [
        'invisible' => [
          ':input[name="printfriendly_page_header"]' => [
            ['value' => t('default_logo')],
          ],
        ],
      ],
    ];

    $form['printfriendly_features']['printfriendly_click_delete'] = [
      '#type' => 'select',
      '#title' => t('Click-to-delete'),
      '#options' => [
        '0' => 'Allow',
        '1' => 'Not Allow',
      ],
      '#default_value' => $config->get('printfriendly_click_delete', '0'),
    ];

    $form['printfriendly_features']['printfriendly_images'] = [
      '#type' => 'select',
      '#title' => t('Images'),
      '#options' => [
        '0' => 'Include',
        '1' => 'Exclude',
      ],
      '#default_value' => $config->get('printfriendly_images', '0'),
    ];

    $form['printfriendly_features']['printfriendly_image_style'] = [
      '#type' => 'select',
      '#title' => t('Image style'),
      '#options' => [
        'right' => 'Align Right',
        'left' => 'Align Left',
        'none' => 'Align None',
        'block' => 'Center/Block',
      ],
      '#default_value' => $config->get('printfriendly_image_style', 'right'),
    ];

    $form['printfriendly_features']['printfriendly_email'] = [
      '#type' => 'select',
      '#title' => t('Email'),
      '#options' => [
        '0' => 'Allow',
        '1' => 'Not Allow',
      ],
      '#default_value' => $config->get('printfriendly_email', '0'),
    ];

    $form['printfriendly_features']['printfriendly_pdf'] = [
      '#type' => 'select',
      '#title' => t('PDF'),
      '#options' => [
        '0' => 'Allow',
        '1' => 'Not Allow',
      ],
      '#default_value' => $config->get('printfriendly_pdf', '0'),
    ];

    $form['printfriendly_features']['printfriendly_print'] = [
      '#type' => 'select',
      '#title' => t('Print'),
      '#options' => [
        '0' => 'Allow',
        '1' => 'Not Allow',
      ],
      '#default_value' => $config->get('printfriendly_print', '0'),
    ];

    $form['printfriendly_features']['printfriendly_custom_css'] = [
      '#type' => 'textfield',
      '#description' => t('Put full path of the file like http://devt.drupalchamp.org/sites/default/files/printfriendly.css'),
      '#title' => t('Custom css url'),
      '#default_value' => $config->get('printfriendly_custom_css', ''),
    ];

    $form['support-link'] = [
      '#markup' => 'Need help or have suggestions? <a href="mailto:support@printfriendly.com">Support@PrintFriendly.com</a>',
      '#weight' => 1000,
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('printfriendly.settings');
    // Print "<pre>$btn_group_name"; print_r($form_state->getValues()); print "</pre>"; die;.
    $config->set('printfriendly_display', $form_state->getValue('printfriendly_display'));
    $config->set('printfriendly_page_header', $form_state->getValue('printfriendly_page_header'));
    $config->set('printfriendly_page_custom_header', $form_state->getValue('printfriendly_page_custom_header'));
    $config->set('printfriendly_tagline', $form_state->getValue('printfriendly_tagline'));
    if ($form_state->getValue('printfriendly_page_header') == 'default_logo') {
      $config->set('printfriendly_page_custom_header', '');
      $config->set('printfriendly_tagline', '');
    }
    $config->set('printfriendly_click_delete', $form_state->getValue('printfriendly_click_delete'));
    $config->set('printfriendly_images', $form_state->getValue('printfriendly_images'));
    $config->set('printfriendly_image_style', $form_state->getValue('printfriendly_image_style'));
    $config->set('printfriendly_email', $form_state->getValue('printfriendly_email'));
    $config->set('printfriendly_pdf', $form_state->getValue('printfriendly_pdf'));
    $config->set('printfriendly_print', $form_state->getValue('printfriendly_print'));
    $config->set('printfriendly_custom_css', $form_state->getValue('printfriendly_custom_css'));

    $config->set('printfriendly_image', $form_state->getValue('printfriendly_image'));
    if ($form_state->getValue('printfriendly_image') == 'custom-button-img-url') {
      $config->set('custom_button_img_url', $form_state->getValue('custom_button_img_url'));
    }
    else {
      $config->set('custom_button_img_url', '');
    }

    $config->save();

    return parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['printfriendly.settings'];
  }

}
