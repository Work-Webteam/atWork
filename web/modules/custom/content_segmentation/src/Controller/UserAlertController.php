<?php

/**
 * @file
 * Contains \Drupal\content_segmentation\Controller\UserAlertController.
 */

namespace Drupal\content_segmentation\Controller;

use PDO;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\HttpFoundation\Cookie;
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
    // Get the hirachically segments of the current user.
    // Get Current User emp code.
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    //$user = \Drupal\user\Entity\User::load(99838);
    if ($user->hasField('field_emp')){
      $parent_tid = $user->field_emp->target_id; // the parent term id

      // Get emp code children tree values.
      $vid = 'emp';
      $depth = NULL; // 1 to get only immediate children, NULL to load entire tree
      $load_entities = FALSE; // True will return loaded entities rather than ids
      $tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $parent_tid, $depth, $load_entities);
      $tids = array_column($tids, 'tid');
      array_unshift($tids, $parent_tid);

      // NOTE: if the taxonomy tree is note needed, then use only $parent_tid
      // $tids = [$parent_tid];

      ///Get the active messages ids that hit the hierachically segments of the user,
      // And that haven't been closed by the user.
      $query = db_query("SELECT DISTINCT pm.field_message_target_id as nid, dv.weight, nd.nid,
                         FROM `paragraph__field_message` pm
                         INNER JOIN `draggableviews_structure` dv
                          ON (pm.entity_id = dv.entity_id)
                         INNER JOIN `node_field_data` nd
                          ON (pm.field_message_target_id = nd.nid)
                         WHERE dv.view_name = 'content_emp'
                         AND dv.view_display = 'page_2'
                         AND nd.status = '1'
                         AND nd.type = 'messages'
                         AND pm.entity_id IN (SELECT entity_id FROM `paragraph__field_emp`
                                              WHERE bundle = 'messages_emp'
                                              AND field_emp_target_id IN (:tids[]) )
                         AND nd.nid NOT IN (
                                         SELECT uat.uuid_alert
                                         FROM {cs_user_alert_track} uat
                                         WHERE uat.uuid_user = :cookie)
                         ORDER BY dv.weight, nd.nid DESC", array(':cookie' => $_COOKIE['Drupal_visitor_UUID'], ':tids[]' => $tids));

      $records = $query->fetchAllAssoc('nid', PDO::FETCH_ASSOC);
      if (isset($records)) {
        foreach ($records as $record) {
          $entity = Node::load($record['nid']);
          if (isset($_COOKIE['Drupal_visitor_UUID'])) {
            $alert = entity_view($entity, 'teaser');

            $user_alert = [
              '#theme' => 'cs_user_alert_message',
              '#is_closeable' => TRUE,
              '#id' => $record['nid'],
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
  }

  /**
   * Respond to a user clicking to close an alert.
   */
  public function closeAlert() {
    $id = $_GET['message'];

    $alert = Node::load($id);
    if (!$alert) {
      return;
    }

    $fields = array(
      'uuid_alert' => $alert->id(),
      'uuid_user' => $_COOKIE['Drupal_visitor_UUID'],
    );

    $query = \Drupal::database()->delete('cs_user_alert_track');
    $query->condition('uuid_alert', $alert->id());
    $query->condition('uuid_user', $_COOKIE['Drupal_visitor_UUID']);
    $query->execute();

    db_insert('cs_user_alert_track')->fields($fields)->execute();

    return new JsonResponse();
  }
}
