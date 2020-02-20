<?php

namespace Drupal\photos\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\photos\PhotosAlbum;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Content controller for user images.
 */
class PhotosUserImagesController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(Connection $connection, CurrentPathStack $current_path, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_manager, RendererInterface $renderer, RequestStack $request_stack, RouteMatchInterface $route_match) {
    $this->connection = $connection;
    $this->currentPath = $current_path;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_manager;
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('path.current'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Check if user can view account photos.
    $uid = $this->routeMatch->getParameter('user');
    $account = $this->entityTypeManager->getStorage('user')->load($uid);
    if ($this->currentUser()->hasPermission('view photo') && (!$account || _photos_access('viewUser', $account))) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * Photos page title.
   */
  public function title() {
    // Generate title.
    $user = $this->currentUser();
    $uid = $this->routeMatch->getParameter('user');
    if ($uid <> $user->id()) {
      $account = $this->entityTypeManager->getStorage('user')->load($uid);
      return $this->t("@name's images", ['@name' => $account->getDisplayName()]);
    }
    else {
      return $this->t('My Images');
    }
  }

  /**
   * Returns content for recent images.
   *
   * @return array
   *   An array of markup for the user images content.
   */
  public function contentOverview() {
    $config = $this->config('photos.settings');
    $build = [];
    // Get current user and account.
    // @todo a lot of duplicate code can be consolidated in these controllers.
    $user = $this->currentUser();
    $uid = $this->routeMatch->getParameter('user');
    $account = FALSE;
    if ($uid && is_numeric($uid)) {
      $account = $this->entityTypeManager->getStorage('user')->load($uid);
    }
    if (!$account) {
      throw new NotFoundHttpException();
    }
    $order = explode('|', $config->get('photos_display_imageorder'));
    $order = PhotosAlbum::orderValueChange($order[0], $order[1]);
    $get_field = $this->requestStack->getCurrentRequest()->query->get('field');
    $get_sort = $this->requestStack->getCurrentRequest()->query->get('sort');
    $column = $get_field ? Html::escape($get_field) : 0;
    $sort = $get_sort ? Html::escape($get_sort) : 0;
    $term = PhotosAlbum::orderValue($column, $sort, $config->get('photos_display_viewpager'), $order);
    if ($account->id()) {
      // @todo move query out.
      $query = $this->connection->select('file_managed', 'f')
        ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $query->join('photos_image', 'p', 'p.fid = f.fid');
      $query->join('node_field_data', 'n', 'n.nid = p.pid');
      $query->join('users_field_data', 'u', 'u.uid = f.uid');
      $query->fields('f', ['fid']);
      $query->addField('n', 'title', 'node_title');
      $query->condition('f.uid', $account->id());
      $query->orderBy($term['order']['column'], $term['order']['sort']);
      $query->limit($term['limit']);
      $query->addTag('node_access');
      $results = $query->execute();

      $slideshow = '';
      $album['links'] = PhotosAlbum::orderLinks('photos/user/' . $account->id() . '/image', $account->album['image']['count'], $slideshow, 1);
    }
    $com = $config->get('photos_comment');
    $edit = 0;
    if ($account->id() && $user->id() && $account->id() == $user->id()) {
      $edit = 1;
    }

    $style_name = $config->get('photos_display_list_imagesize');
    foreach ($results as $result) {
      $photos_image = new PhotosImage($result->fid);
      $image = $photos_image->load();
      $image->title = Html::escape($image->title);
      $image->des = Html::escape($image->des);
      $image->view = [
        '#theme' => 'photos_image_html',
        '#style_name' => $style_name,
        '#image' => $image,
      ];
      $image->url = Url::fromUri('base:photos/image/' . $image->fid)->toString();

      if ($com) {
        $image->links['comment'] = [
          '#theme' => 'photos_comment_count',
          '#comcount' => $image->comcount,
          '#url' => $image->url,
        ];
      }
      // Check count image views variable.
      $photos_image_count = $config->get('photos_image_count');
      if (!$photos_image_count && isset($image->count)) {
        $image->links['count'] = $this->formatPlural($image->count, '@cou visit', '@cou visits', ['@cou' => $image->count]);
      }
      if ($account->id() || !empty($image->uid) && $image->uid <> $account->id()) {
        $account = $this->entityTypeManager->getStorage('user')->load($image->uid);
      }
      // Get username.
      $name = '';
      if (!empty($image->uid)) {
        $account = $this->entityTypeManager->getStorage('user')->load($image->uid);
        $name_render_array = [
          '#theme' => 'username',
          '#account' => $account,
        ];
        $name = $this->renderer->render($name_render_array);
      }
      $image->links['info'] = $this->t('Uploaded on @time by @name in @title', [
        '@name' => $name,
        '@time' => $this->dateFormatter->format($image->created, 'short'),
        '@title' => Link::fromTextAndUrl($image->node_title, Url::fromUri('base:photos/album/' . $image->pid))->toString(),
      ]);

      $image->class = [
        'title_class' => '',
        'des_class' => '',
      ];
      $image->id = [
        'des_edit' => '',
        'title_edit' => '',
      ];
      $image->ajax['del_id'] = '';
      if ($edit) {
        $image->ajax['edit_url'] = $image->url . '/update';
        $current_path = $this->currentPath->getPath();
        $image->ajax['edit_link'] = Link::fromTextAndUrl($this->t('Edit'), Url::fromUri('base:photos/image/' . $image->fid . '/edit', [
          'query' => [
            'destination' => $current_path,
            'pid' => $image->pid,
            'uid' => $image->uid,
          ],
          'attributes' => [
            'class' => ['colorbox-load', 'photos-edit-edit'],
          ],
        ]));

        $image->class = [
          'title_class' => ' jQueryeditable_edit_title',
          'des_class' => ' jQueryeditable_edit_des',
        ];
        $image->id = [
          'des_edit' => ' id="photos-image-edit-des-' . $image->fid . '"',
          'title_edit' => ' id="photos-image-edit-title-' . $image->fid . '"',
        ];
        $image->ajax['del_id'] = 'id="photos_ajax_del_' . $image->fid . '"';
        $image->ajax['del_link'] = Link::fromTextAndUrl($this->t('Delete'), Url::fromUri('base:photos/image/' . $image->fid . '/delete', [
          'query' => [
            'destination' => $current_path,
          ],
          'attributes' => [
            'class' => ['photos-edit-delete'],
            'alt' => 'photos_ajax_del_' . $image->fid,
          ],
        ]));

        // Set cover link.
        $url_query = $this->getDestinationArray();
        $cover_url = Url::fromRoute('photos.album.update.cover', [
          'node' => $image->pid,
          'file' => $image->fid,
        ],
        [
          'query' => $url_query,
        ]);
        $image->links['cover'] = Link::fromTextAndUrl($this->t('Set to Cover'), $cover_url);
      }
      $album['view'][] = [
        '#theme' => 'photos_image_view',
        '#image' => $image,
        '#display_type' => 'list',
      ];
    }
    if (isset($album['view'][0])) {
      // Set pager.
      $album['pager'] = ['#type' => 'pager'];
      $render_album_view = [
        '#theme' => 'photos_album_view',
        '#album' => $album,
        '#node' => NULL,
      ];
      $content = $this->renderer->render($render_album_view);
    }
    else {
      if ($account <> FALSE) {
        $content = $this->t('@name has not uploaded any images yet.', ['@name' => $account->getDisplayName()]);
      }
      else {
        $content = $this->t('No images have been uploaded yet.');
      }
    }
    $build = [
      '#markup' => $content,
    ];

    return $build;
  }

}
