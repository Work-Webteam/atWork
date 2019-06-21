<?php

namespace Drupal\photos\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Image view controller.
 */
class PhotosImageController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, EntityManagerInterface $entity_manager, LibraryDiscoveryInterface $library_discovery, RouteMatchInterface $route_match) {
    $this->configFactory = $config_factory;
    $this->connection = $connection;
    $this->entityManager = $entity_manager;
    $this->libraryDiscovery = $library_discovery;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('library.discovery'),
      $container->get('current_route_match')
    );
  }

  /**
   * Set page title.
   */
  public function getTitle() {
    // Get node object.
    $fid = $this->routeMatch->getParameter('file');
    $title = $this->connection->query("SELECT title FROM {photos_image} WHERE fid = :fid", [':fid' => $fid])->fetchField();
    return $title;
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Check if user can view account photos.
    $fid = $this->routeMatch->getParameter('file');
    if (_photos_access('imageView', $fid)) {
      // Allow access.
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * Returns content for single image.
   *
   * @return string
   *   A HTML-formatted string with the administrative page content.
   */
  public function contentOverview() {
    $fid = $this->routeMatch->getParameter('file');
    $config = $this->config('photos.settings');
    if (!is_numeric($fid)) {
      throw new NotFoundHttpException();
    }
    $photos_image = new PhotosImage($fid);
    $image = $photos_image->load();

    if (!$image) {
      throw new NotFoundHttpException();
    }

    $node = $this->entityManager->getStorage('node')->load($image->pid);
    if (_photos_access('imageEdit', $node)) {
      $image->ajax['edit_url'] = Url::fromUri('base:photos/image/' . $image->fid . '/update')->toString();
      // Set album cover.
      $image->links['cover'] = Link::createFromRoute($this->t('Set to Cover'), 'photos.album.update.cover', [
        'node' => $image->pid,
        'file' => $fid,
      ],
      [
        'query' => $this->getDestinationArray(),
      ]);
    }
    $image->class = [
      'title_class' => '',
      'des_class' => '',
    ];
    $image->id = [
      'des_edit' => '',
      'title_edit' => '',
    ];
    $edit = _photos_access('imageEdit', $node);
    if ($edit) {
      // Image edit link.
      $url = Url::fromUri('base:photos/image/' . $image->fid . '/edit', [
        'query' => [
          'destination' => 'photos/image/' . $image->fid,
        ],
        'attributes' => [
          'class' => ['colorbox-load', 'photos-edit-edit'],
        ],
      ]);
      $image->ajax['edit_link'] = Link::fromTextAndUrl($this->t('Edit'), $url);

      $image->class = [
        'title_class' => ' jQueryeditable_edit_title',
        'des_class' => ' jQueryeditable_edit_des',
      ];
      $image->id = [
        'des_edit' => ' id="photos-image-edit-des-' . $image->fid . '"',
        'title_edit' => ' id="photos-image-edit-title-' . $image->fid . '"',
      ];
      $jeditable_library = $this->libraryDiscovery->getLibraryByName('photos', 'photos.jeditable');
    }
    if (_photos_access('imageDelete', $node)) {
      // Image delete link.
      // @todo cancel should go back to image. Confirm to album.
      $url = Url::fromUri('base:photos/image/' . $image->fid . '/delete', [
        'query' => [
          'destination' => 'node/' . $image->pid,
        ],
        'attributes' => [
          'class' => ['colorbox-load', 'photos-edit-delete'],
        ],
      ]);
      $image->ajax['del_link'] = Link::fromTextAndUrl($this->t('Delete'), $url);
    }
    if ($config->get('photos_comment')) {
      // Comment integration.
      $render_comment = [
        '#theme' => 'photos_comment_count',
        '#comcount' => $image->comcount,
      ];
      $image->links['comment'] = $render_comment;
    }

    // Album images.
    $pager_type = 'pid';
    $pager_id = $image->pid;
    $data = isset($image->data) ? unserialize($image->data) : [];
    $style_name = isset($data['view_imagesize']) ? $data['view_imagesize'] : $config->get('photos_display_view_imagesize');

    $image->links['pager'] = $photos_image->pager($pager_id, $pager_type);
    $image->view = [
      '#theme' => 'photos_image_html',
      '#style_name' => $style_name,
      '#image' => $image,
      '#cache' => [
        'tags' => [
          'photos:image:' . $fid,
        ],
      ],
    ];

    // Get comments.
    $image->comment['view'] = $photos_image->comments($image->comcount, $node);
    // Check count image views variable.
    $photos_image_count = $config->get('photos_image_count');
    $image->disable_photos_image_count = $photos_image_count;
    if (!$photos_image_count) {
      $count = 1;
      $this->connection->update('photos_image')
        ->fields(['count' => $count])
        ->expression('count', 'count + :count', [':count' => $count])
        ->condition('fid', $fid)
        ->execute();
    }
    $image->title = Html::escape($image->title);
    $image->des = Html::escape($image->des);

    $GLOBALS['photos'][$image->fid . '_pid'] = $image->pid;

    $image_view = [
      '#theme' => 'photos_image_view',
      '#image' => $image,
      '#display_type' => 'view',
      '#cache' => [
        'tags' => [
          'photos:image:' . $fid,
        ],
      ],
    ];
    // Check for Jeditable library.
    // @todo move to static public function?
    if ($edit && isset($jeditable_library['js']) && file_exists($jeditable_library['js'][0]['data'])) {
      $image_view['#attached']['library'][] = 'photos/photos.jeditable';
    }

    return $image_view;
  }

}
