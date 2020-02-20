<?php

namespace Drupal\photos\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\photos\PhotosAlbum;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller to display recent images.
 */
class PhotosImagesRecentController extends ControllerBase {

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
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(Connection $connection, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_manager, RendererInterface $renderer, RequestStack $request_stack) {
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_manager;
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('request_stack')
    );
  }

  /**
   * Returns content for recent images.
   *
   * @return array
   *   An array containing markup for the page content.
   */
  public function contentOverview() {
    $config = $this->config('photos.settings');
    $build = [];
    // Prepare query.
    $order = explode('|', $config->get('photos_display_imageorder'));
    $order = PhotosAlbum::orderValueChange($order[0], $order[1]);
    // Recent images default sort should be created desc.
    $get_field = $this->requestStack->getCurrentRequest()->query->get('field');
    $get_sort = $this->requestStack->getCurrentRequest()->query->get('sort');
    $column = $get_field ? Html::escape($get_field) : 'created';
    $sort = $get_sort ? Html::escape($get_sort) : 'desc';
    $term = PhotosAlbum::orderValue($column, $sort, $config->get('photos_display_viewpager'), $order);
    // Query recent images.
    $query = $this->connection->select('file_managed', 'f')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('photos_image', 'p', 'p.fid = f.fid');
    $query->join('node_field_data', 'n', 'n.nid = p.pid');
    $query->join('users_field_data', 'u', 'u.uid = f.uid');
    $query->fields('f', ['fid']);
    $query->orderBy($term['order']['column'], $term['order']['sort']);
    $query->limit($term['limit']);
    $query->addTag('node_access');
    $results = $query->execute();

    // Image count.
    $album['links'] = PhotosAlbum::orderLinks('photos/image', PhotosAlbum::getCount('site_image'), 0, 1);
    $com = $config->get('photos_comment');

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
      // @todo check permission to edit.
      $edit = 0;
      if ($edit) {
        $image->ajax['edit_url'] = $image->url . '/update';
        $current_path = $this->currentPath->getPath();
        $image->ajax['edit_link'] = Link::fromTextAndUrl($this->t('Edit'), Url::fromUri('base:photos/image/' . $image->fid . '/edit', [
          'query' => [
            'destination' => $current_path,
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
        $content = $this->t('@name has not uploaded any images yet.', ['@name' => $account->name]);
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
