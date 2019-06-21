<?php

namespace Drupal\forward\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure settings for this module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle information manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundleInfoManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs a Forward settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info
   *   The entity type bundle information manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfo $bundle_info, FileSystem $file_system) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfoManager = $bundle_info;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forward_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['forward.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $forward_config = $this->config('forward.settings');
    $settings = $forward_config->get();

    // Entity Types.
    $form['forward_entities'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity Types'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $entity_types = $this->entityTypeManager->getDefinitions();
    $options = [];
    $defaults = [];
    foreach ($entity_types as $type => $info) {
      if (is_a($info, 'Drupal\Core\Entity\ContentEntityType')) {
        // Filter some entity types out.
        if (!in_array($type, [
          'block_content',
          'consumer',
          'contact_message',
          'content_moderation_state',
          'crop',
          'file',
          'menu_link_content',
          'node',
          'oauth2_token',
          'redirect',
          'shortcut',
          'webform_submission',
        ])) {
          $options[$type] = $info->getLabel();
          if (!empty($settings['forward_entity_' . $type])) {
            $defaults[] = $type;
          }
        }
      }
    }
    $form['forward_entities']['forward_entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types'),
      '#options' => $options,
      '#default_value' => $defaults,
      '#description' => $this->t('Choose entity types to show Forward on.'),
    ];
    // Bundles.
    foreach ($entity_types as $type => $info) {
      if (is_a($info, 'Drupal\Core\Entity\ContentEntityType')) {
        if (!empty($settings['forward_entity_' . $type]) || ($type == 'node')) {
          $bundles = $this->bundleInfoManager->getBundleInfo($type);
          if (count($bundles) == 1) {
            foreach ($bundles as $bundle => $bundle_info) {
              $form['forward_entities']['forward_' . $type . '_types'] = [
                '#type' => 'hidden',
                '#value' => $bundle,
              ];
            }
          }
          else {
            $options = [];
            $defaults = [];
            foreach ($bundles as $bundle => $bundle_info) {
              $options[$bundle] = $bundle_info['label'];
              if (!empty($settings['forward_' . $type . '_' . $bundle])) {
                $defaults[] = $bundle;
              }
            }
            $form['forward_entities']['forward_' . $type . '_types'] = [
              '#type' => 'checkboxes',
              '#title' => $this->t($info->getLabel() . ' bundles'),
              '#options' => $options,
              '#default_value' => $defaults,
              '#description' => $this->t('Choose @type bundles to show Forward on.', ['@type' => $info->getLowercaseLabel()]),
            ];
          }
        }
      }
    }
    // View Modes.
    $modes = ['full' => $this->t('Full entity'), 'teaser' => $this->t('Teaser')];
    $options = [];
    $defaults = [];
    foreach ($modes as $mode => $info) {
      $options[$mode] = $info;
      if ($settings['forward_view_' . $mode]) {
        $defaults[] = $mode;
      }
    }
    $form['forward_entities']['forward_view_modes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('View modes'),
      '#options' => $options,
      '#default_value' => $defaults,
      '#description' => $this->t('Choose view modes to show Forward on.'),
    ];
    // Interface.
    $form['forward_interface'] = [
      '#type' => 'details',
      '#title' => $this->t('Interface'),
      '#open' => FALSE,
    ];
    $form['forward_interface']['forward_interface_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Interface type'),
      '#default_value' => $settings['forward_interface_type'],
      '#options' => [
        'link' => $this->t('Link to separate page'),
        'form' => $this->t('Display inline form'),
      ],
      '#description' => $this->t('Choose how the Forward form is reached from the displayed entity. Inline forms are displayed within a collapsible fieldset on the entity being forwarded.'),
    ];
    $form['forward_interface']['forward_interface_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of interface element'),
      '#default_value' => $settings['forward_interface_title'],
      '#description' => $this->t('Set the text of the link or fieldset title. Replacement tokens may be used.'),
      '#required' => TRUE,
    ];
    $form['forward_interface']['forward_interface_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $settings['forward_interface_weight'],
      '#description' => $this->t('Set the weight of the link or inline form for positioning.'),
      '#required' => TRUE,
    ];
    $form['forward_interface']['forward_link_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Link Options'),
      '#states' => [
        'visible' => [
          ':input[name=forward_interface_type]' => ['value' => 'link'],
        ],
      ],
    ];
    $form['forward_interface']['forward_link_options']['forward_link_inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place the link inline with other node links'),
      '#default_value' => $settings['forward_link_inline'],
      '#description' => $this->t('Place inline with links like "Read more" and "Add comment". If not placed inline, or the entity is not a node, the link is added to the content area.'),
    ];
    $form['forward_interface']['forward_link_options']['forward_link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => $settings['forward_link_title'],
      '#maxlength' => 255,
      '#description' => $this->t('Page title for the Forward form page.'),
      '#required' => TRUE,
    ];
    $form['forward_interface']['forward_link_options']['forward_link_style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Style'),
      '#default_value' => $settings['forward_link_style'],
      '#options' => [
        0 => $this->t('Text only'),
        1 => $this->t('Icon only'),
        2 => $this->t('Icon and text'),
      ],
      '#description' => $this->t('Select the visual style of the link.'),
    ];
    $form['forward_interface']['forward_link_options']['forward_link_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to custom icon'),
      '#default_value' => $settings['forward_link_icon'],
      '#description' => $this->t('The path to your custom link icon instead of the default icon. Example: sites/default/files/icon.png'),
    ];
    $form['forward_interface']['forward_link_options']['forward_link_noindex'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate a noindex meta tag on the forward page') . ' (name="robots", content="noindex, nofollow")',
      '#default_value' => $settings['forward_link_noindex'],
    ];
    $form['forward_interface']['forward_link_options']['forward_link_nofollow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate a nofollow tag on the forward link (rel="nofollow")'),
      '#default_value' => $settings['forward_link_nofollow'],
    ];
    // Forward Form.
    $form['forward_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Forward Form'),
      '#open' => FALSE,
    ];
    $form['forward_form']['forward_form_instructions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Instructions'),
      '#default_value' => $settings['forward_form_instructions'],
      '#rows' => 5,
      '#description' => $this->t('The instructions to display above the form.  Replacement tokens may be used.  This field may contain HTML.'),
    ];
    $form['forward_form']['form_display_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Form Fields'),
    ];
    $form['forward_form']['form_display_options']['forward_form_display_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a link to the page being forwarded'),
      '#default_value' => $settings['forward_form_display_page'],
    ];
    $form['forward_form']['form_display_options']['forward_form_display_subject'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the email message subject'),
      '#default_value' => $settings['forward_form_display_subject'],
    ];
    $form['forward_form']['form_display_options']['forward_form_display_body'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the email introductory message text'),
      '#default_value' => $settings['forward_form_display_body'],
    ];
    $form['forward_form']['personal_messages'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal Message Field'),
    ];
    $form['forward_form']['personal_messages']['forward_personal_message'] = [
      '#type' => 'select',
      '#title' => $this->t('Personal message'),
      '#options' => [0 => 'Hidden', 1 => 'Optional', 2 => 'Required'],
      '#default_value' => $settings['forward_personal_message'],
      '#description' => $this->t('Choose whether the personal message field on the form will be hidden, optional or required.'),
    ];
    $form['forward_form']['personal_messages']['forward_personal_message_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow HTML in personal messages'),
      '#return_value' => 1,
      '#default_value' => $settings['forward_personal_message_filter'],
      '#description' => $this->t('Filter XSS and all tags not allowed below from the personal message.  Otherwise any HTML in the message will be converted to plain text.'),
      '#states' => [
        'invisible' => [
          ':input[name=forward_personal_message]' => ['value' => 0],
        ],
      ],
    ];
    $form['forward_form']['personal_messages']['forward_personal_message_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML tags'),
      '#default_value' => $settings['forward_personal_message_tags'],
      '#description' => $this->t('List of tags (separated by commas) that will be allowed if HTML is enabled above.  Defaults to: p,br,em,strong,cite,code,ul,ol,li,dl,dt,dd'),
      '#states' => [
        'invisible' => [
          ':input[name=forward_personal_message]' => ['value' => 0],
        ],
      ],
    ];
    $form['forward_form']['forward_max_recipients'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum allowed recipients'),
      '#default_value' => $settings['forward_max_recipients'],
      '#description' => $this->t('The maximum number of recipients for the email.'),
    ];
    $form['forward_form']['forward_max_recipients_error'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Maximum recipients error'),
      '#default_value' => $settings['forward_max_recipients_error'],
      '#rows' => 5,
      '#description' => $this->t('This text appears if a user tries to send to more recipients than allowed. The value of the maximum recipient limit will appear in place of @number in the message presented to users.'),
    ];
    $form['forward_form']['forward_form_confirmation'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Confirmation message'),
      '#default_value' => $settings['forward_form_confirmation'],
      '#rows' => 5,
      '#description' => $this->t('The thank you message displayed after the user successfully submits the form.  Replacement tokens may be used.'),
    ];
    // Defaults for Message to Recipient.
    $form['forward_email_defaults'] = [
      '#type' => 'details',
      '#title' => $this->t('Forward Email'),
      '#open' => FALSE,
    ];
    $form['forward_email_defaults']['forward_email_logo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to custom logo'),
      '#default_value' => $settings['forward_email_logo'],
      '#maxlength' => 256,
      '#description' => $this->t('The path to the logo you would like to use instead of the site default logo. Example: sites/default/files/logo.png'),
      '#required' => FALSE,
    ];
    $form['forward_email_defaults']['forward_email_from_address'] = [
      '#type' => 'email',
      '#title' => $this->t('From address'),
      '#default_value' => $settings['forward_email_from_address'],
      '#maxlength' => 254,
      '#required' => TRUE,
    ];
    $form['forward_email_defaults']['forward_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject line'),
      '#default_value' => $settings['forward_email_subject'],
      '#maxlength' => 256,
      '#description' => $this->t('Email subject line. Replacement tokens may be used.'),
      '#required' => TRUE,
    ];
    $form['forward_email_defaults']['forward_email_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Introductory message text'),
      '#default_value' => $settings['forward_email_message'],
      '#rows' => 5,
      '#description' => $this->t('Introductory text that appears above the entity being forwarded. Replacement tokens may be used. The sender may be able to add their own personal message after this.  This field may contain HTML.'),
    ];
    // Post processing filters.
    $filter_options = [];
    $filter_options[''] = $this->t('- None -');
    foreach (filter_formats($this->currentUser()) as $key => $format) {
      $filter_options[$key] = $format->label();
    }
    if (count($filter_options) > 1) {
      $form['forward_filter_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Filter'),
        '#open' => FALSE,
      ];
      $form['forward_filter_options']['forward_filter_format'] = [
        '#type' => 'select',
        '#title' => $this->t('Filter format'),
        '#default_value' => $settings['forward_filter_format'],
        '#options' => $filter_options,
        '#description' => $this->t('Select a filter to apply to the email message body. A filter with <a href="http://drupal.org/project/pathologic">Pathologic</a> assigned to it will convert relative links to absolute links. &nbsp;<a href="http://drupal.org/project/modules">More filters</a>.'),
      ];
    }
    // Access Control.
    $form['forward_access_control'] = [
      '#type' => 'details',
      '#title' => $this->t('Access Control'),
      '#open' => FALSE,
      '#description' => $this->t('The email build process normally uses anonymous visitor permissions to render the entity being forwarded.  This is appropriate for most sites.  If you bypass anonymous access control, and the person doing the forward is logged in, the permissions of the logged in account are used instead.  Bypassing anonymous access control creates a potential security risk because privileged information could be sent to people who are not authorized to view it.'),
    ];
    $form['forward_access_control']['forward_bypass_access_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bypass anonymous access control'),
      '#default_value' => $settings['forward_bypass_access_control'],
      '#description' => $this->t('<em>Warning: selecting this option has security implications.</em>'),
    ];
    // Flood Control.
    $form['forward_flood_control_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Flood Control'),
      '#open' => FALSE,
    ];
    $form['forward_flood_control_options']['forward_flood_control_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Flood control limit'),
      '#default_value' => $settings['forward_flood_control_limit'],
      '#options' => [
        '1' => '1',
        '5' => '5',
        '10' => '10',
        '15' => '15',
        '20' => '20',
        '25' => '25',
        '30' => '30',
        '35' => '35',
        '40' => '40',
        '50' => '50',
      ],
      '#description' => $this->t('How many times a user can use the form in a one hour period. This will help prevent the forward module from being used for spamming.'),
    ];
    $form['forward_flood_control_options']['forward_flood_control_error'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Flood control error'),
      '#default_value' => $settings['forward_flood_control_error'],
      '#rows' => 5,
      '#description' => $this->t('This text appears if a user exceeds the flood control limit.  The value of the flood control limit setting will appear in place of @number in the message presented to users.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate link icon path if provided.
    if ($form_state->getValue('forward_link_icon')) {
      $image = File::create();
      $image->setFileUri($form_state->getValue('forward_link_icon'));
      $image->setFilename($this->fileSystem->basename($image->getFileUri()));
      $errors = file_validate_is_image($image);
      if (count($errors)) {
        $form_state->setErrorByName('forward_link_icon', $this->t("The link icon path '@path' is invalid.", ['@path' => $form_state->getValue('forward_link_icon')]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save entity types and bundles.
    $values = $form_state->getValue('forward_entity_types');
    $values['node'] = TRUE;
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $type => $info) {
      if (is_a($info, 'Drupal\Core\Entity\ContentEntityType')) {
        if (!empty($values[$type])) {
          $this->config('forward.settings')
            ->set('forward_entity_' . $type, TRUE);
        }
        else {
          $this->config('forward.settings')
            ->set('forward_entity_' . $type, FALSE);
        }
        $bundles = $this->bundleInfoManager->getBundleInfo($type);
        foreach ($bundles as $bundle => $bundle_info) {
          $bundle_values = $form_state->getValue('forward_' . $type . '_types');
          if (!empty($values[$type]) && !empty($bundle_values[$bundle])) {
            $this->config('forward.settings')
              ->set('forward_' . $type . '_' . $bundle, TRUE);
          }
          else {
            $this->config('forward.settings')
              ->set('forward_' . $type . '_' . $bundle, FALSE);
          }
          // If only one bundle, it gets the same setting as its type.
          if (count($bundles) == 1) {
            $this->config('forward.settings')
              ->set('forward_' . $type . '_' . $bundle, $this->config('forward.settings')
                ->get('forward_entity_' . $type));
          }
        }
      }
    }
    // Save view modes.
    $modes = ['full', 'teaser'];
    $values = $form_state->getValue('forward_view_modes');
    foreach ($modes as $mode) {
      if (!empty($values[$mode])) {
        $this->config('forward.settings')->set('forward_view_' . $mode, TRUE);
      }
      else {
        $this->config('forward.settings')->set('forward_view_' . $mode, FALSE);
      }
    }
    // Save all other settings.
    $this->config('forward.settings')
      ->set('forward_interface_type', $form_state->getValue('forward_interface_type'))
      ->set('forward_interface_title', $form_state->getValue('forward_interface_title'))
      ->set('forward_interface_weight', $form_state->getValue('forward_interface_weight'))
      ->set('forward_link_inline', $form_state->getValue('forward_link_inline'))
      ->set('forward_link_title', $form_state->getValue('forward_link_title'))
      ->set('forward_link_style', $form_state->getValue('forward_link_style'))
      ->set('forward_link_icon', $form_state->getValue('forward_link_icon'))
      ->set('forward_link_noindex', $form_state->getValue('forward_link_noindex'))
      ->set('forward_link_nofollow', $form_state->getValue('forward_link_nofollow'))
      ->set('forward_form_instructions', $form_state->getValue('forward_form_instructions'))
      ->set('forward_form_display_page', $form_state->getValue('forward_form_display_page'))
      ->set('forward_form_display_subject', $form_state->getValue('forward_form_display_subject'))
      ->set('forward_form_display_body', $form_state->getValue('forward_form_display_body'))
      ->set('forward_form_confirmation', $form_state->getValue('forward_form_confirmation'))
      ->set('forward_personal_message', $form_state->getValue('forward_personal_message'))
      ->set('forward_personal_message_filter', $form_state->getValue('forward_personal_message_filter'))
      ->set('forward_personal_message_tags', $form_state->getValue('forward_personal_message_tags'))
      ->set('forward_email_logo', $form_state->getValue('forward_email_logo'))
      ->set('forward_email_from_address', $form_state->getValue('forward_email_from_address'))
      ->set('forward_email_subject', $form_state->getValue('forward_email_subject'))
      ->set('forward_email_message', $form_state->getValue('forward_email_message'))
      ->set('forward_filter_format', $form_state->getValue('forward_filter_format'))
      ->set('forward_bypass_access_control', $form_state->getValue('forward_bypass_access_control'))
      ->set('forward_flood_control_limit', $form_state->getValue('forward_flood_control_limit'))
      ->set('forward_flood_control_error', $form_state->getValue('forward_flood_control_error'))
      ->set('forward_max_recipients', $form_state->getValue('forward_max_recipients'))
      ->set('forward_max_recipients_error', $form_state->getValue('forward_max_recipients_error'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
