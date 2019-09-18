<?php

namespace Drupal\rate\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rate\RateEntityVoteWidget;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure rate settings for the site.
 */
class RateSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rate_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['rate.settings'];
  }

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Http Client object.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * RateSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \GuzzleHttp\Client $http_client
   *   Http client object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Client $http_client) {
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rate.settings');

    $form['widget_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Widget settings'),
      '#open' => TRUE,
    ];

    $form['widget_settings']['use_ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use AJAX'),
      '#default_value' => $config->get('use_ajax', FALSE),
      '#description' => $this->t('Record vote via AJAX.'),
    ];

    $form['bot'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bot detection'),
      '#description' => $this->t('Bots can be automatically banned from voting if they rate more than a given amount of votes within one minute or hour. This threshold is configurable below. Votes from the same IP-address will be ignored forever after reaching this limit.'),
      '#collapsbile' => FALSE,
      '#collapsed' => FALSE,
    ];

    $threshold_options = array_combine([0, 10, 25, 50, 100, 250, 500, 1000], [
      0,
      10,
      25,
      50,
      100,
      250,
      500,
      1000,
    ]);
    $threshold_options[0] = $this->t('disable');

    $form['bot']['bot_minute_threshold'] = [
      '#type' => 'select',
      '#title' => $this->t('1 minute threshold'),
      '#options' => $threshold_options,
      '#default_value' => $config->get('bot_minute_threshold'),
    ];

    $form['bot']['bot_hour_threshold'] = [
      '#type' => 'select',
      '#title' => $this->t('1 hour threshold'),
      '#options' => $threshold_options,
      '#default_value' => $config->get('bot_hour_threshold'),
    ];

    $form['bot']['botscout_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BotScout.com API key'),
      '#default_value' => $config->get('botscout_key'),
      '#description' => $this->t('Rate will check the voters IP against the BotScout database if it has an API key. You can request a key at %url.', ['%url' => 'http://botscout.com/getkey.htm']),
    ];

    // Start new table form to select the widget for each content type.
    $form['rate_types_enabled'] = [
      '#type' => 'details',
      '#title' => $this->t('Content types with enabled rate widgets'),
      '#description' => $this->t('If you set any type here to - None -, already existing data will remain untouched.'),
      '#open' => TRUE,
    ];

    // Create a table to store the entities and their widgets.
    $header = [
      $this->t('Entity'),
      $this->t('Entity Type'),
      $this->t('Status'),
      $this->t('Rate Widget'),
    ];
    $form['enabled_rate_widgets'] = [
      '#type' => 'table',
      '#weight' => 100,
      '#header' => $header,
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_type_ids = array_keys($entity_types);
    $enabled_types_widgets = $config->get('enabled_types_widgets') ? $config->get('enabled_types_widgets') : [];

    // Get the widget types for the widget select table field.
    $widget_type_options = RateEntityVoteWidget::getRateWidgets();

    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Only allow voting on content entities.
      // Also, don't allow voting on votes, that would be weird.
      if ($entity_type->getBundleOf() && $entity_type->getBundleOf() != 'vote') {
        $bundles = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple();
        $content_entitites_with_bundles[] = $entity_type->getBundleOf();
        if (!empty($bundles)) {
          foreach ($bundles as $bundle) {
            $default_value = (isset($enabled_types_widgets[$entity_type->getBundleOf()][$bundle->id()])) ? $enabled_types_widgets[$entity_type->getBundleOf()][$bundle->id()]['widget_type'] : '';
            $widget_status = ($default_value != '') ? 'ACTIVE' : '';
            $entity_full = 'enabled|' . $entity_type->getBundleOf() . '|' . $bundle->id();

            $form['enabled_rate_widgets'][$entity_full]['entity_type'] = ['#plain_text' => $bundle->label()];
            $form['enabled_rate_widgets'][$entity_full]['entity_type_id'] = ['#plain_text' => $entity_type->getBundleOf()];
            $form['enabled_rate_widgets'][$entity_full]['status'] = ['#plain_text' => $widget_status];
            $form['enabled_rate_widgets'][$entity_full]['widget_type'] = [
              '#type' => 'select',
              '#empty_value' => '',
              '#required' => FALSE,
              '#options' => $widget_type_options,
              '#default_value' => $default_value,
            ];
          }
        }
      }
      elseif ($entity_type->getGroup() == 'content' && !in_array($entity_type->getBundleEntityType(), $entity_type_ids) && $entity_type_id != 'vote_result') {

        $default_value = (isset($enabled_types_widgets[$entity_type_id][$entity_type_id])) ? $enabled_types_widgets[$entity_type_id][$entity_type_id]['widget_type'] : '';
        $widget_status = ($default_value != '') ? 'ACTIVE' : '';
        $entity_full = 'enabled|' . $entity_type_id . '|' . $entity_type_id;

        $form['enabled_rate_widgets'][$entity_full]['entity_type'] = ['#plain_text' => $entity_type->getLabel()->__toString()];
        $form['enabled_rate_widgets'][$entity_full]['entity_type_id'] = ['#plain_text' => $entity_type_id];
        $form['enabled_rate_widgets'][$entity_full]['status'] = ['#plain_text' => $widget_status];
        $form['enabled_rate_widgets'][$entity_full]['widget_type'] = [
          '#type' => 'select',
          '#empty_value' => '',
          '#required' => FALSE,
          '#options' => $widget_type_options,
          '#default_value' => $default_value,
        ];
      }
    }
    $form['rate_types_enabled']['enabled_rate_widgets'] = $form['enabled_rate_widgets'];
    unset($form['enabled_rate_widgets']);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $messenger = $this->messenger();
    if ($form_state->getValue(['botscout_key'])) {
      $uri = "http://botscout.com/test/?ip=84.16.230.111&key=" . $form_state->getValue(['botscout_key']);
      try {
        $response = $this->httpClient->get($uri, ['headers' => ['Accept' => 'text/plain']]);
        $data = (string) $response->getBody();
        $status_code = $response->getStatusCode();
        if (empty($data)) {
          $messenger->addWarning($this->t('An empty response was returned from botscout.'));
        }
        elseif ($status_code == 200) {
          if ($data{0} == 'Y' || $data{0} == 'N') {
            $messenger->addStatus($this->t('Rate has succesfully contacted the BotScout server.'));
          }
          else {
            $form_state->setErrorByName('botscout_key', $this->t('Invalid API-key.'));
          }
        }
        else {
          $messenger->addWarning($this->t('Rate was unable to contact the BotScout server.'));
        }
      }
      catch (RequestException $e) {
        $messenger->addWarning($this->t('An error occurred contacting BotScout.'));
        watchdog_exception('rate', $e);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('rate.settings');

    $enabled_rate_widgets = [];
    $enabled_types_widgets = [];
    $values = $form_state->getValues();
    $enabled_rate_widgets = $form_state->getValue('enabled_rate_widgets');

    foreach ($enabled_rate_widgets as $index => $value) {
      if (!empty($value['widget_type'])) {
        if (stripos($index, 'enabled|') !== FALSE && $value) {
          $entity_bundle = explode('|', str_ireplace('enabled|', '', $index));
          if (isset($enabled_types_widgets[$entity_bundle[0]])) {
            $enabled_types_widgets[$entity_bundle[0]][$entity_bundle[1]] = [
              'widget_type' => $value['widget_type'],
              'use_ajax' => $form_state->getValue('use_ajax'),
            ];
          }
          else {
            $enabled_types_widgets[$entity_bundle[0]] = [];
            $enabled_types_widgets[$entity_bundle[0]][$entity_bundle[1]] = [
              'widget_type' => $value['widget_type'],
              'use_ajax' => $form_state->getValue('use_ajax'),
            ];
          }
        }
      }
      else {
        unset($enabled_types_widgets[$index]);
      }
    }

    $config->set('enabled_types_widgets', $enabled_types_widgets)
      ->set('bot_minute_threshold', $form_state->getValue('bot_minute_threshold'))
      ->set('bot_hour_threshold', $form_state->getValue('bot_hour_threshold'))
      ->set('botscout_key', $form_state->getValue('botscout_key'))
      ->set('use_ajax', $form_state->getValue('use_ajax'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
