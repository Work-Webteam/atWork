<?php

namespace Drupal\rate\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
   * Constructs a Vote Controller.
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

    $widget_type_options = [
      "fivestar" => "Fivestar",
      "number_up_down" => "Number Up / Down",
      "thumbs_up" => "Thumbs Up",
      "thumbs_up_down" => "Thumbs Up / Down",
      "yesno" => "Yes / No",
    ];

    $form['widget_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Widget Type'),
      '#options' => $widget_type_options,
      '#default_value' => $config->get('widget_type'),
    ];

    $form['use_ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use AJAX'),
      '#default_value' => $config->get('use_ajax'),
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

    $form['rate_types_enabled'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Entity types with Rate widgets enabled:'),
      '#description' => $this->t('If you disable any type here, already existing data will remain untouched.'),
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_type_ids = array_keys($entity_types);
    $enabled_types_bundles = $config->get('enabled_types_bundles');

    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Only allow voting on content entities.
      // Also, don't allow voting on votes, that would be weird.
      if ($entity_type->getBundleOf() && $entity_type->getBundleOf() != 'vote') {
        $bundles = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple();
        $content_entitites_with_bundles[] = $entity_type->getBundleOf();
        if (!empty($bundles)) {
          $form['rate_types_enabled'][$entity_type_id . '_enabled'] = [
            '#type' => 'details',
            '#open' => FALSE,
            '#title' => $entity_type->getBundleOf(),
          ];
          foreach ($bundles as $bundle) {
            $default_value = 0;
            if (isset($enabled_types_bundles[$entity_type->getBundleOf()]) && in_array($bundle->id(), $enabled_types_bundles[$entity_type->getBundleOf()])) {
              $default_value = 1;
            }
            $form['rate_types_enabled'][$entity_type_id . '_enabled']['enabled|' . $entity_type->getBundleOf() . '|' . $bundle->id()] = [
              '#type' => 'checkbox',
              '#title' => $bundle->label(),
              '#default_value' => $default_value,
            ];
          }
        }
      }
      elseif ($entity_type->getGroup() == 'content' &&
        !in_array($entity_type->getBundleEntityType(), $entity_type_ids) &&
        $entity_type_id != 'vote_result') {
        $default_value = (isset($enabled_types_bundles[$entity_type_id])) ? 1 : 0;
        $form['rate_types_enabled']['enabled|' . $entity_type_id . '|' . $entity_type_id] = [
          '#type' => 'checkbox',
          '#title' => $entity_type_id,
          '#default_value' => $default_value,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();
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

    $enabled_types_bundles = [];
    $values = $form_state->getValues();
    foreach ($values as $index => $value) {
      if (stripos($index, 'enabled|') !== FALSE && $value) {
        // Retrieve the entity and bundle values (entity first).
        $entity_bundle = explode('|', str_ireplace('enabled|', '', $index));
        // Key on entity and create an child array of bundles.
        if (isset($enabled_types_bundles[$entity_bundle[0]])) {
          $enabled_types_bundles[$entity_bundle[0]][] = $entity_bundle[1];
        }
        else {
          $enabled_types_bundles[$entity_bundle[0]] = [];
          $enabled_types_bundles[$entity_bundle[0]][] = $entity_bundle[1];
        }
      }
    }
    $config->set('enabled_types_bundles', $enabled_types_bundles);

    $config->set('widget_type', $form_state->getValue('widget_type'))
      ->set('bot_minute_threshold', $form_state->getValue('bot_minute_threshold'))
      ->set('bot_hour_threshold', $form_state->getValue('bot_hour_threshold'))
      ->set('botscout_key', $form_state->getValue('botscout_key'))
      ->set('use_ajax', $form_state->getValue('use_ajax'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
