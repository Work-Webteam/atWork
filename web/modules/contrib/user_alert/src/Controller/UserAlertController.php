<?php

/**
 * @file
 * Contains \Drupal\user_alert\Controller\UserAlertController.
 */

namespace Drupal\user_alert\Controller;

use PDO;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Unicode;
use Drupal\user_alert\Entity\UserAlert;
use Drupal\Core\Render\RendererInterface;
use Drupal\user_alert\UserAlertInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Drupal\user_alert\UserAlertTypeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Returns responses for Node routes.
 */
class UserAlertController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a UserAlertController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add content links for available content types.
   *
   * Redirects to user-alert/add/[user_alert_type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the user alert types that can be added; however,
   *   if there is only one user alert type defined for the site, the function
   *   will return a RedirectResponse to the user alert add page for that one node
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'user_alert_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition('user_alert_type')->getListCacheTags(),
      ],
    ];

    $content = array();

    // Only use user alert types the user has access to.
    foreach ($this->entityManager()->getStorage('user_alert_type')->loadMultiple() as $type) {
      $access = $this->entityManager()->getAccessControlHandler('user_alert')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the add listing if only one user alert type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('entity.user_alert.add', array('user_alert_type' => $type->id()));
    }

    $build['#content'] = $content;
    return $build;
  }

  /**
   * Provides the user alert submission form.
   *
   * @param \Drupal\user_alert\UserAlertTypeInterface $user_alert_type
   *   The user alert type entity for the user alert.
   *
   * @return array
   *   A user alert submission form.
   */
  public function add(UserAlertTypeInterface $user_alert_type) {
    $user_alert = $this->entityManager()->getStorage('user_alert')->create(array(
      'type' => $user_alert_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($user_alert);
    return $form;
  }

  /**
   * The _title_callback for the entity.user_alert.add route.
   *
   * @param \Drupal\user_alert\UserAlertTypeInterface $user_alert_type
   *   The selected user alert type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(UserAlertTypeInterface $user_alert_type) {
    return $this->t('Create @name', array('@name' => $user_alert_type->label()));
  }

  /**
   * Gather alerts for the current user and return them. Exclude ones already closed.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing alerts in html.
   */
  public function displayAlert() {

    $module_handler = \Drupal::service('module_handler'); 
    if ($module_handler->moduleExists('translation')) {
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    } else {
      $language = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    $output = '';
    $query = db_query("SELECT ua.id FROM {user_alert} ua WHERE ua.uuid NOT IN (SELECT uat.uuid_alert FROM {user_alert_track} uat WHERE uat.uuid_user = :cookie) ORDER BY ua.id DESC", array(':cookie' => $_COOKIE['Drupal_visitor_UUID']));
    $records = $query->fetchAllAssoc('id', PDO::FETCH_ASSOC);

    if (isset($records)) {
      foreach ($records as $record) {
        $entity = UserAlert::load($record['id']);
        if (isset($_COOKIE['Drupal_visitor_UUID'])) {
          $alert = entity_view($entity, 'teaser');

          $user_alert = [
            '#theme' => 'user_alert_message',
            '#is_closeable' => TRUE,
            '#id' => $record['id'],
            '#alert' => render($alert),
          ];

          $output .= render($user_alert);
        }
      }
    }

    return new JsonResponse(
      array(
        'alerts' => $output
      )
    );
  }

  /**
   * Respond to a user clicking to close an alert.
   */
  public function closeAlert() {
    $id = $_GET['message'];

    $alert = UserAlert::load($id);
    if (!$alert) {
      return;
    }

    $fields = array(
      'uuid_alert' => $alert->uuid(),
      'uuid_user' => $_COOKIE['Drupal_visitor_UUID'],
    );

    db_insert('user_alert_track')->fields($fields)->execute();

    return new JsonResponse();
  }
}
