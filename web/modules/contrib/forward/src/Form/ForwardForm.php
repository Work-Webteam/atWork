<?php

namespace Drupal\forward\Form;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\Utility\Token;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Unicode;
use Drupal\forward\Event\EntityForwardEvent;
use Drupal\forward\Event\EntityPreforwardEvent;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Forward a page to a friend.
 */
class ForwardForm extends FormBase implements BaseFormIdInterface {

  /**
   * The entity being forwarded.
   *
   * @var Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The flood interface.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $floodInterface;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The mail service.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailer;

  /**
   * The link generation service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * The email validation service.
   *
   * @var Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * The settings for this form.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs a Forward Form.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity being forwarded.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Inject services.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   * @param \Drupal\Core\Flood\FloodInterface $flood_interface
   *   The flood interface.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Mail\MailManager $mailer
   *   The mail service.
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   The link generation service.
   * @param Egulias\EmailValidator\EmailValidator
   *   The email validation service.
   */
  public function injectServices(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, Connection $database, Token $token_service, FloodInterface $flood_interface, AccountSwitcherInterface $account_switcher, RendererInterface $renderer, ContainerAwareEventDispatcher $event_dispatcher, MailManager $mailer, LinkGenerator $link_generator, EmailValidator $email_validator) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->database = $database;
    $this->tokenService = $token_service;
    $this->floodInterface = $flood_interface;
    $this->accountSwitcher = $account_switcher;
    $this->renderer = $renderer;
    $this->eventDispatcher = $event_dispatcher;
    $this->mailer = $mailer;
    $this->linkGenerator = $link_generator;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forward_form_' . $this->entity->getEntityTypeId() . '_' . $this->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'forward_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['forward.form'];
  }

  /**
   * Clean a string.
   */
  private function cleanString($string) {
    // Strip embedded URLs.
    $string = preg_replace('|https?://www\.[a-z\.0-9]+|i', '', $string);
    $string = preg_replace('|www\.[a-z\.0-9]+|i', '', $string);
    return $string;
  }

  /**
   * Splits a string into email addresses via comma or newline separators.
   *
   * @param string $text
   *   The string that contains one or more email addresses.
   *
   * @return array
   *   A array of unique email addresses.
   */
  private function splitEmailAddresses($text) {
    $emails = preg_split('/[;, \r\n]+/', $text);
    $emails = array_filter($emails);
    $emails = array_unique($emails);
    return $emails;
  }

  /**
   * Get a token.
   */
  private function getToken(FormStateInterface $form_state = NULL) {
    $token = [];
    if ($form_state && $form_state->getValue('name')) {
      // Harden the name field against abuse. @see https://www.drupal.org/node/2793891
      $token = ['forward' => ['sender_name' => $this->cleanString($form_state->getValue('name'))]];
    }
    elseif ($this->currentUser()->isAuthenticated()) {
      $token = [
        'forward' => [
          'sender_name' => $this->currentUser()
            ->getDisplayName(),
        ],
      ];
    }
    if ($form_state && $form_state->getValue('email')) {
      $token['forward']['sender_email'] = $form_state->getValue('email');
    }
    elseif ($this->currentUser()->isAuthenticated()) {
      $token['forward']['sender_email'] = $this->currentUser()->getEmail();
    }
    if ($form_state) {
      $token['forward']['entity'] = $form_state->get('#entity');
    }
    // Allow other modules to add more tokens.
    if ($extra_tokens = $this->moduleHandler->invokeAll('forward_token', [$form_state])) {
      $token += $extra_tokens;
    }
    return $token;
  }

  /**
   * Get the event name used for Flood control.
   */
  private function getFloodControlEventName() {
    return 'forward.send';
  }

  /**
   * Determine if a given display is valid for an entity.
   */
  private function isValidDisplay(EntityInterface $entity, $view_mode) {
    // Assume the display is valid.
    $valid = TRUE;

    // Build display name.
    if ($entity->getEntityType()->hasKey('bundle')) {
      // Bundled entity types, e.g. node.
      $display_name = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $view_mode;
    }
    else {
      // Entity types without bundles, e.g. user.
      $display_name = $entity->getEntityTypeId() . '.' . $view_mode;
    }

    // Attempt load.
    $display = $this->entityTypeManager->getStorage('entity_view_display')->load($display_name);
    if ($display) {
      // If the display loads, it exists in configuration, and status can be checked.
      $valid = FALSE;
      if ($display->status()) {
        $valid = TRUE;
      }
    }

    return $valid;
  }

  /**
   * Logging.
   */
  private function logEvent(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_id = $entity->id();

    $uid = $this->currentUser()->id();
    $path = substr($entity->toUrl()->toString(), 1);
    $ip_address = $this->requestStack->getCurrentRequest()->getClientIp();
    $timestamp = REQUEST_TIME;

    // Insert into log.
    $this->database->insert('forward_log')
      ->fields([
        'type' => $entity_type,
        'id' => $entity_id,
        'path' => $path,
        'action' => 'SENT',
        'timestamp' => $timestamp,
        'uid' => $uid,
        'hostname' => $ip_address,
      ])
      ->execute();

    // Update statistics.
    $this->database->merge('forward_statistics')
      ->key([
        'type' => $entity_type,
        'bundle' => $bundle,
        'id' => $entity_id,
      ])
      ->fields([
        'forward_count' => 1,
        'last_forward_timestamp' => $timestamp,
      ])
      ->expression('forward_count', 'forward_count + 1')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $settings = NULL) {
    if (!$settings) {
      $settings = $this->config('forward.settings')->get();
    }
    $this->settings = $settings;
    $form_state->set('#entity', $this->entity);
    $token = $this->getToken($form_state);
    $langcode = $this->entity->language()->getId();

    // Build the form.
    if ($settings['forward_interface_type'] == 'link') {
      // Set the page title dynamically.
      $form['#title'] = $this->tokenService->replace($settings['forward_link_title'], $token, ['langcode' => $langcode]);
    }
    else {
      // Inline form.
      $form['message'] = [
        '#type' => 'details',
        '#title' => $this->tokenService->replace($settings['forward_link_title'], $token, ['langcode' => $langcode]),
        '#description' => '',
        '#open' => FALSE,
        '#weight' => $settings['forward_interface_weight'],
      ];
    }
    $form['message']['instructions'] = [
      '#markup' => $this->tokenService->replace($settings['forward_form_instructions'], $token, ['langcode' => $langcode]),
    ];
    $form['message']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email address'),
      '#maxlength' => 254,
      '#required' => TRUE,
    ];
    $form['message']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    if ($settings['forward_max_recipients'] > 1) {
      $form['message']['recipient'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Send to email address'),
        '#default_value' => '',
        '#cols' => 50,
        '#rows' => 2,
        '#description' => $this->t('Enter multiple addresses on separate lines or separate them with commas.'),
        '#required' => TRUE,
      ];
    }
    else {
      $form['message']['recipient'] = [
        '#type' => 'email',
        '#title' => $this->t('Send to'),
        '#maxlength' => 254,
        '#description' => $this->t('Enter the email address of the recipient.'),
        '#required' => TRUE,
      ];
    }
    if ($settings['forward_form_display_page']) {
      $form['message']['page'] = [
        '#type' => 'item',
        '#title' => $this->t('You are going to email the following:'),
        '#markup' => $this->linkGenerator->generate($this->entity->label(), $this->entity->toUrl()),
      ];
    }
    if ($settings['forward_form_display_subject']) {
      $form['message']['subject'] = [
        '#type' => 'item',
        '#title' => $this->t('The message subject will be:'),
        '#markup' => $this->tokenService->replace($settings['forward_email_subject'], $token, ['langcode' => $langcode]),
      ];
    }
    if ($settings['forward_form_display_body']) {
      $form['message']['body'] = [
        '#type' => 'item',
        '#title' => $this->t('The introductory message text will be:'),
        '#markup' => $this->tokenService->replace($settings['forward_email_message'], $token, ['langcode' => $langcode]),
      ];
    }
    if ($settings['forward_personal_message']) {
      $form['message']['message'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Your personal message'),
        '#default_value' => '',
        '#cols' => 50,
        '#rows' => 5,
        '#description' => $settings['forward_personal_message_filter'] ? $this->t('These HTML tags are allowed in this field: @tags.', ['@tags' => $settings['forward_personal_message_tags']]) : $this->t('HTML is not allowed in this field.'),
        '#required' => ($settings['forward_personal_message'] == 2),
      ];
    }

    // Submit button.
    if ($settings['forward_interface_type'] == 'form') {
      // When using a collapsible form, move submit button into fieldset.
      $form['message']['actions'] = ['#type' => 'actions'];
      $form['message']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send Message'),
        '#weight' => 100,
      ];
    }
    else {
      // When using a separate form page, use actions directly.
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send Message'),
      ];
    }

    // Default name and email address to logged in user.
    if ($this->currentUser()->isAuthenticated()) {
      if ($this->currentUser()->hasPermission('override email address')) {
        $form['message']['email']['#default_value'] = $this->currentUser()->getEmail();
      }
      else {
        // User not allowed to change sender email address.
        $form['message']['email']['#type'] = 'hidden';
        $form['message']['email']['#value'] = $this->currentUser()->getEmail();
      }
      $form['message']['name']['#default_value'] = $this->currentUser()->getDisplayName();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->currentUser()->hasPermission('override flood control')) {
      $event = $this->getFloodControlEventName();
      if (!$this->floodInterface->isAllowed($event, $this->settings['forward_flood_control_limit'])) {
        $message = new FormattableMarkup($this->settings['forward_flood_control_error'], ['@number' => $this->settings['forward_flood_control_limit']]);
        $form_state->setErrorByName('', $message);
      }
    }

    $recipients = $this->splitEmailAddresses($form_state->getValue('recipient'));
    if (count($recipients) > $this->settings['forward_max_recipients']) {
      $message = new FormattableMarkup($this->settings['forward_max_recipients_error'], ['@number' => $this->settings['forward_max_recipients']]);
      $form_state->setErrorByName('', $message);
    }
    foreach ($recipients as $recipient) {
      if (!$this->emailValidator->isValid($recipient)) {
        $message = $this->t('The email address %mail is not valid.', ['%mail' => $recipient]);
        $form_state->setErrorByName('', $message);
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form values.
    $entity = $form_state->get('#entity');
    $recipients = $this->splitEmailAddresses($form_state->getValue('recipient'));

    // Use the entity language to drive translation.
    $langcode = $entity->language()->getId();

    // Switch to anonymous user session if logged in, unless bypassing access control.
    $switched = FALSE;
    if ($this->currentUser()
      ->isAuthenticated() && empty($this->settings['forward_bypass_access_control'])) {
      $this->accountSwitcher->switchTo(new AnonymousUserSession());
      $switched = TRUE;
    }

    try {
      // Build the message subject line.
      $token = $this->getToken($form_state);
      $params['subject'] = $this->tokenService->replace($this->settings['forward_email_subject'], $token, ['langcode' => $langcode]);

      // Build the entity content.
      $view_mode = '';
      $elements = [];
      if ($entity->access('view')) {
        $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
        $view_mode = 'forward';
        if ($this->isValidDisplay($entity, $view_mode)) {
          $elements = $view_builder->view($entity, $view_mode, $langcode);
        }
        if (empty($elements)) {
          $view_mode = 'teaser';
          if ($this->isValidDisplay($entity, $view_mode)) {
            $elements = $view_builder->view($entity, $view_mode, $langcode);
          }
        }
        if (empty($elements)) {
          $view_mode = 'full';
          $elements = $view_builder->view($entity, $view_mode, $langcode);
        }
      }
      // Prevent recursion.
      $elements['#forward_build'] = TRUE;
      $content = $this->renderer->render($elements);

      // Build the header line.
      $header = ['#markup' => $this->tokenService->replace($this->settings['forward_email_message'], $token, ['langcode' => $langcode])];

      // Build the personal message if present.
      $message = '';
      if ($this->settings['forward_personal_message']) {
        if ($this->settings['forward_personal_message_filter']) {
          // HTML allowed in personal message, so filter out anything but the allowed tags.
          $raw_values = $form_state->getUserInput();
          $allowed_tags = explode(',', $this->settings['forward_personal_message_tags']);
          $message = !empty($raw_values['message']) ? Xss::filter($raw_values['message'], $allowed_tags) : '';
          $message = ['#markup' => nl2br($message)];
        }
        else {
          // HTML not allowed in personal message, so use the sanitized version converted to plain text.
          $message = ['#plain_text' => nl2br($form_state->getValue('message'))];
        }
      }

      // Build the email body.
      $render_array = [
        '#theme' => 'forward',
        '#email' => $form_state->getValue('email'),
        '#header' => $header,
        '#message' => $message,
        '#settings' => $this->settings,
        '#entity' => $entity,
        '#content' => $content,
        '#view_mode' => $view_mode,
      ];

      // Allow modules to alter the render array for the message.
      $this->moduleHandler->alter('forward_mail_pre_render', $render_array, $form_state);

      // Render the message.
      $params['body'] = $this->renderer->render($render_array);

      // Apply filters such as Pathologic for link correction.
      if ($this->settings['forward_filter_format']) {
        // This filter was setup by the Forward administrator for this purpose only,
        // whose permission to run the filter was checked at that time.
        // Therefore, no need to check filter access again here.
        $params['body'] = check_markup($params['body'], $this->settings['forward_filter_format'], $langcode);
      }

      // Allow modules to alter the final message body.
      $this->moduleHandler->alter('forward_mail_post_render', $params['body'], $form_state);
    }
    catch (Exception $e) {
      if ($switched) {
        $this->accountSwitcher->switchBack();
        $switched = FALSE;
      }
      $this->logger('forward')->error($e->getMessage());
    }

    // Switch back to logged in user if necessary.
    if ($switched) {
      $this->accountSwitcher->switchBack();
    }

    // Build the from email address and Reply-To.
    $from = $this->settings['forward_email_from_address'];
    if (empty($from)) {
      $from = $this->config('system.site')->get('mail');
    }
    if (empty($from)) {
      $site_mail = ini_get('sendmail_from');
    }
    $params['headers']['Reply-To'] = trim(Unicode::mimeHeaderEncode($form_state->getValue('name')) . ' <' . $form_state->getValue('email') . '>');

    // Prepare for Event dispatch.
    $account = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser()->id());

    // Event dispatch - before forwarding.
    $event = new EntityPreforwardEvent($account, $entity, [
      'account' => $account,
      'entity' => $entity,
    ]);
    $this->eventDispatcher->dispatch(EntityPreforwardEvent::EVENT_NAME, $event);

    // Send the email to the recipient. Set the key so the Forward mail plugin
    // is only used if the default mail plugin is still the core PHP Mail plugin.
    // If another module such as SMTP has been enabled, then that will be used.
    $mail_configuration = $this->config('system.mail')->get('interface');
    $key = ($mail_configuration['default'] == 'php_mail') ? 'send_entity' : 'mail_entity';
    foreach ($recipients as $recipient) {
      $this->mailer->mail('forward', $key, $recipient, $langcode, $params, $from);
    }

    // Log this for tracking purposes.
    $this->logEvent($entity);

    // Register event for flood control.
    $event = $this->getFloodControlEventName();
    $this->floodInterface->register($event);

    // Event dispatch - after forwarding.
    $event = new EntityForwardEvent($account, $entity, [
      'account' => $account,
      'entity' => $entity,
    ]);
    $this->eventDispatcher->dispatch(EntityForwardEvent::EVENT_NAME, $event);

    // Allow modules to post process the forward.
    $this->moduleHandler->invokeAll('forward_entity', [
      $account,
      $entity,
      $form_state,
    ]);

    // Display a confirmation message.
    $message = $this->tokenService->replace($this->settings['forward_form_confirmation'], $token, ['langcode' => $langcode]);
    if ($message) {
      drupal_set_message($message);
    }

    // Redirect back to entity page unless a redirect is already set.
    if ($this->settings['forward_interface_type'] == 'link') {
      if (!$form_state->getRedirect()) {
        $form_state->setRedirectUrl($entity->toUrl());
      }
    }
  }

}
