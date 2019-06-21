<?php

namespace Drupal\photos\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\photos\PhotosAlbum;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Album view controller.
 */
class PhotosAlbumController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The current request stack.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, DateFormatterInterface $date_formatter, EntityManagerInterface $entity_manager, ImageFactory $image_factory, LibraryDiscoveryInterface $library_discovery, RequestStack $request_stack, RouteMatchInterface $route_match) {
    $this->configFactory = $config_factory;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
    $this->entityManager = $entity_manager;
    $this->imageFactory = $image_factory;
    $this->libraryDiscovery = $library_discovery;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('entity.manager'),
      $container->get('image.factory'),
      $container->get('library.discovery'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * Set page title.
   */
  public function getTitle() {
    // Get node object.
    $node = $this->routeMatch->getParameter('node');
    $title = 'Album: ' . $node->getTitle();
    return $title;
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Get node.
    $node = $this->routeMatch->getParameter('node');
    if (!$node) {
      // Not found.
      throw new NotFoundHttpException();
    }
    // Check access.
    $access_op = 'album';
    if ($account->hasPermission('view photo') && _photos_access($access_op, $node)) {
      // Allow access.
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * Returns an overview of recent albums and photos.
   *
   * @return string
   *   A HTML-formatted string with the administrative page content.
   */
  public function albumView() {
    $config = $this->config('photos.settings');
    // Get node object.
    $album = [];
    $node = $this->routeMatch->getParameter('node');
    $nid = $node->id();
    // Get order or set default order.
    $order = explode('|', (isset($node->album['imageorder']) ? $node->album['imageorder'] : $config->get('photos_display_imageorder')));
    $order = PhotosAlbum::orderValueChange($order[0], $order[1]);
    $limit = isset($node->album['viewpager']) ? $node->album['viewpager'] : $config->get('photos_display_viewpager');
    $get_field = $this->requestStack->getCurrentRequest()->query->get('field');
    $get_sort = $this->requestStack->getCurrentRequest()->query->get('sort');
    $column = $get_field ? Html::escape($get_field) : '';
    $sort = isset($get_sort) ? Html::escape($get_sort) : '';
    $term = PhotosAlbum::orderValue($column, $sort, $limit, $order);
    // Album image's query.
    // @todo move to PhotosAlbum()->getImages().
    $query = $this->connection->select('file_managed', 'f')->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('photos_image', 'p', 'p.fid = f.fid');
    $query->join('users_field_data', 'u', 'u.uid = f.uid');
    $query->fields('f', ['fid'])
      ->condition('p.pid', $nid)
      ->orderBy($term['order']['column'], $term['order']['sort'])
      ->limit($term['limit']);
    if ($term['order']['column'] <> 'f.fid') {
      $query->orderBy('f.fid', 'DESC');
    }
    $results = $query->execute();

    // Check comment settings.
    $com = $config->get('photos_comment');
    // Check node access.
    $edit = $node->access('update');
    $del = $node->access('delete');
    $style_name = isset($node->album['list_imagesize']) ? $node->album['list_imagesize'] : $config->get('photos_display_list_imagesize');
    // Necessary when upgrading from D6 to D7.
    $image_styles = image_style_options(FALSE);
    if (!isset($image_styles[$style_name])) {
      $style_name = $config->get('photos_display_list_imagesize');
    }

    // Process images.
    foreach ($results as $result) {
      $photos_image = new PhotosImage($result->fid);
      $image = $photos_image->load();
      $image->title = Html::escape($image->title);
      $image->des = Html::escape($image->des);

      $title = $image->title;

      // Build image view.
      $image_view_array = [
        '#theme' => 'photos_image_html',
        '#style_name' => $style_name,
        '#image' => $image,
      ];
      $image->view = $image_view_array;

      // Image link.
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
      if (!$photos_image_count && $image->count) {
        $image->links['count'] = $this->formatPlural($image->count, '@count visit', '@count visits', ['@count' => $image->count]);
      }
      $image->links['info'] = $this->t('Uploaded on @time by @name', [
        '@name' => $image->name,
        '@time' => $this->dateFormatter->format($image->created, 'short'),
      ]);

      $image->class = [
        'title_class' => '',
        'des_class' => '',
      ];
      $image->id = [
        'des_edit' => '',
        'title_edit' => '',
      ];
      // Edit links.
      if ($edit) {
        $destination = $this->getDestinationArray();
        $image->ajax['edit_url'] = $image->url . '/update';
        // Edit link.
        $url = Url::fromUri('base:photos/image/' . $image->fid . '/edit', [
          'query' => [
            'destination' => $destination['destination'],
            'pid' => $nid,
            'uid' => $image->uid,
          ],
          'attributes' => [
            'class' => ['colorbox-load', 'photos-edit-edit'],
          ],
        ]);
        $image->ajax['edit_link'] = Link::fromTextAndUrl($this->t('Edit'), $url);

        // Jeditable inline editing integration.
        // @todo add setting to enable Jeditable?
        $image->class = [
          'title_class' => ' jQueryeditable_edit_title',
          'des_class' => ' jQueryeditable_edit_des',
        ];
        $image->id = [
          'des_edit' => ' id="photos-image-edit-des-' . $image->fid . '"',
          'title_edit' => ' id="photos-image-edit-title-' . $image->fid . '"',
        ];
        $jeditable_library = $this->libraryDiscovery->getLibraryByName('photos', 'photos.jeditable');

        // Link to update album cover.
        if (!isset($node->album['cover']) || isset($node->album['cover']['fid']) && $node->album['cover']['fid'] <> $image->fid) {
          $url = Url::fromRoute('photos.album.update.cover', [
            'node' => $image->pid,
            'file' => $image->fid,
            'destination' => $destination['destination'],
          ]);
          $image->links['cover'] = Link::fromTextAndUrl($this->t('Set to Cover'), $url);
        }
      }
      $image->ajax['del_id'] = '';
      if ($del) {
        $image->ajax['del_id'] = 'id="photos_ajax_del_' . $image->fid . '"';
        $destination = $this->getDestinationArray();
        // Delete link.
        $url = Url::fromUri('base:photos/image/' . $image->fid . '/delete', [
          'query' => [
            'destination' => $destination['destination'],
          ],
          'attributes' => [
            'class' => ['colorbox-load', 'photos-edit-delete'],
          ],
        ]);
        $image->ajax['del_link'] = Link::fromTextAndUrl($this->t('Delete'), $url);
      }

      // Build image view for album.
      // @todo add configurable type (grid etc.).
      $image_view_array = [
        '#theme' => 'photos_image_view',
        '#image' => $image,
        '#display_type' => 'list',
        '#cache' => [
          'tags' => [
            'photos:album:' . $nid,
            'node:' . $nid,
          ],
        ],
      ];
      $album['view'][] = $image_view_array;
    }
    if (isset($album['view'][0])) {
      $album['access']['edit'] = $edit;
      // Node edit link.
      $url = Url::fromUri('base:node/' . $nid . '/edit');
      $album['node_edit_url'] = Link::fromTextAndUrl($this->t('Album settings'), $url);

      // Image management link.
      $url = Url::fromUri('base:node/' . $nid . '/photos');
      $album['image_management_url'] = Link::fromTextAndUrl($this->t('Upload photos'), $url);

      // Album URL.
      $album['album_url'] = Url::fromUri('base:photos/album/' . $nid)->toString();

      $album['links'] = PhotosAlbum::orderLinks('photos/album/' . $nid, 0, 0, 1);
      $cover_style_name = $config->get('photos_cover_imagesize');
      if (isset($node->album['cover']['uri'])) {
        // Album cover view.
        if (isset($node->album['cover']['view'])) {
          $album['cover'] = $node->album['cover']['view'];
        }
        else {
          // @todo is this needed?
          $image_info = $this->imageFactory->get($node->album['cover']['uri']);
          $title = $node->getTitle();
          $album_cover_array = [
            '#theme' => 'image_style',
            '#style_name' => $cover_style_name,
            '#uri' => $node->album['cover']['uri'],
            '#width' => $image_info->getWidth(),
            '#height' => $image_info->getHeight(),
            '#alt' => $title,
            '#title' => $title,
            '#cache' => [
              'tags' => [
                'photos:album:' . $nid,
                'node:' . $nid,
              ],
            ],
          ];
          $album['cover'] = $album_cover_array;
        }
      }
      $album['pager'] = ['#type' => 'pager'];

      // Build album view.
      $album_view_array = [
        '#theme' => 'photos_album_view',
        '#album' => $album,
        '#node' => $node,
        '#cache' => [
          'tags' => [
            'photos:album:' . $nid,
            'node:' . $nid,
          ],
        ],
      ];
      // Check for Jeditable library.
      // @todo move to static public function?
      if ($edit && isset($jeditable_library['js']) && file_exists($jeditable_library['js'][0]['data'])) {
        $album_view_array['#attached']['library'][] = 'photos/photos.jeditable';
      }
      $content = $album_view_array;
    }
    else {
      $content = [
        '#markup' => $this->t('Album is empty'),
        '#cache' => [
          'tags' => [
            'photos:album:' . $nid,
            'node:' . $nid,
          ],
        ],
      ];
    }

    return $content;
  }

}
