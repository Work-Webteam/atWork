<?php

namespace Drupal\photos\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\photos\PhotosAlbum;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-arrange view controller.
 */
class PhotosRearrangeController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_manager, RendererInterface $renderer, RequestStack $request_stack, RouteMatchInterface $route_match) {
    $this->connection = $connection;
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
      $container->get('entity_type.manager'),
      $container->get('renderer'),
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
    $title = $this->t('Rearrange Photos: @title', ['@title' => $node->getTitle()]);
    return $title;
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Check if user can edit this album.
    $node = $this->routeMatch->getParameter('node');
    // Check user for album rearrange.
    $user = $this->routeMatch->getParameter('user');
    if ($user && !is_object($user)) {
      $user = $this->entityTypeManager->getStorage('user')->load($user);
    }
    if ($node && _photos_access('editAlbum', $node)) {
      return AccessResult::allowed();
    }
    elseif ($user && _photos_access('viewUser', $user)) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * Returns photos to be rearranged.
   *
   * @return array
   *   An array of markup for the page content.
   */
  public function contentOverview() {
    $config = $this->config('photos.settings');
    // Get node object.
    $node = $this->routeMatch->getParameter('node');
    $nid = $node->id();
    // Check album type.
    $type = 'album';
    $output = '';
    $build = [];
    $update_button = '';
    if (isset($node->album['imageorder']) && $node->album['imageorder'] <> 'weight|asc') {
      $update_button = ' ' . $this->t('Update album image display order to "Weight - smallest first".');
    }

    // Load library photos.dragndrop.
    $build['#attached']['library'][] = 'photos/photos.dragndrop';
    // Set custom drupalSettings for use in JavaScript file.
    $build['#attached']['drupalSettings']['photos']['pid'] = $nid;

    $images = [];
    if ($type == 'album') {
      // Set custom drupalSettings for use in JavaScript file.
      $build['#attached']['drupalSettings']['photos']['sort'] = 'images';
    }
    $photos_album = new PhotosAlbum($nid);
    $get_limit = $this->requestStack->getCurrentRequest()->query->get('limit');
    $limit = $get_limit ? Html::escape($get_limit) : 50;
    $images = $photos_album->getImages($limit);
    $count = count($images);
    $link_100 = Link::fromTextAndUrl(100, Url::fromUri('base:node/' . $nid . '/photos-rearrange', ['query' => ['limit' => 100]]))->toString();
    $link_500 = Link::fromTextAndUrl(500, Url::fromUri('base:node/' . $nid . '/photos-rearrange', ['query' => ['limit' => 500]]))->toString();
    $output .= $this->t('Limit: @link_100 - @link_500', ['@link_100' => $link_100, '@link_500' => $link_500]);
    $default_message = $this->t('%img_count images to rearrange.', ['%img_count' => $count]);
    $output .= '<div id="photos-sort-message">' . $default_message . $update_button . ' ' . '<span id="photos-sort-updates"></span></div>';
    $output .= '<ul id="photos-sortable" class="photos-sortable">';
    foreach ($images as $image) {
      $title = $image->title;
      // @todo set photos_sort_style variable for custom image style settings.
      $image_sizes = $config->get('photos_size');
      $style_name = key($image_sizes);
      $output .= '<li id="photos_' . $image->fid . '" class="photos-sort-grid ui-state-default">';
      $render_image = [
        '#theme' => 'image_style',
        '#style_name' => $style_name,
        '#uri' => $image->uri,
        '#alt' => $title,
        '#title' => $title,
      ];
      $output .= $this->renderer->render($render_image);

      $output .= '</li>';
    }
    $output .= '</ul>';
    $build['#markup'] = $output;
    $build['#cache'] = [
      'tags' => ['node:' . $nid, 'photos:album:' . $nid],
    ];

    return $build;
  }

  /**
   * Rearrange user albums.
   */
  public function albumRearrange() {
    $config = $this->config('photos.settings');
    $output = '';
    $build = [];
    $account = $this->routeMatch->getParameter('user');
    if ($account && !is_object($account)) {
      $account = $this->entityTypeManager->getStorage('user')->load($account);
    }
    $uid = $account->id();
    // Load library photos.dragndrop.
    $build['#attached']['library'][] = 'photos/photos.dragndrop';
    // Set custom drupalSettings for use in JavaScript file.
    $build['#attached']['drupalSettings']['photos']['uid'] = $uid;
    $build['#attached']['drupalSettings']['photos']['sort'] = 'albums';

    $albums = $this->getAlbums($uid);
    $count = count($albums);
    $limit_uri = Url::fromUri('base:photos/user/' . $uid . '/album-rearrange', ['query' => ['limit' => 100]]);
    $output .= $this->t('Limit: @link', ['@link' => Link::fromTextAndUrl(100, $limit_uri)->toString()]);
    $limit_uri = Url::fromUri('base:photos/user/' . $uid . '/album-rearrange', ['query' => ['limit' => 500]]);
    $output .= ' - ' . Link::fromTextAndUrl(500, $limit_uri)->toString();
    $default_message = $this->t('%album_count albums to rearrange.', ['%album_count' => $count]);
    $output .= '<div id="photos-sort-message">' . $default_message . ' ' . '<span id="photos-sort-updates"></span></div>';
    $output .= '<ul id="photos-sortable" class="photos-sortable">';
    foreach ($albums as $album) {
      $title = $album['title'];
      $cover = $this->entityTypeManager->getStorage('file')->load($album['fid']);
      // @todo set photos_sort_style variable for custom image style settings.
      $image_sizes = $config->get('photos_size');
      $style_name = key($image_sizes);
      $output .= '<li id="photos_' . $album['nid'] . '" class="photos-sort-grid ui-state-default">';
      $render_image = [
        '#theme' => 'image_style',
        '#style_name' => $style_name,
        '#uri' => $cover->getFileUri(),
        '#alt' => $title,
        '#title' => $title,
      ];
      $output .= $this->renderer->render($render_image);

      $output .= '</li>';
    }
    $output .= '</ul>';
    $build['#markup'] = $output;
    $build['#cache'] = [
      'tags' => ['user:' . $uid],
    ];

    return $build;
  }

  /**
   * Get user albums.
   */
  public function getAlbums($uid) {
    $albums = [];
    $get_limit = $this->requestStack->getCurrentRequest()->query->get('limit');
    $limit = $get_limit ? Html::escape($get_limit) : 50;
    $query = $this->connection->select('node_field_data', 'n');
    $query->join('photos_album', 'p', 'p.pid = n.nid');
    $query->fields('n', ['nid', 'title']);
    $query->fields('p', ['wid', 'fid', 'count']);
    $query->condition('n.uid', $uid);
    $query->range(0, $limit);
    $query->orderBy('p.wid', 'ASC');
    $query->orderBy('n.nid', 'DESC');
    $result = $query->execute();

    foreach ($result as $data) {
      if (isset($data->fid) && $data->fid <> 0) {
        $cover_fid = $data->fid;
      }
      else {
        $cover_fid = $this->connection->query("SELECT fid FROM {photos_image} WHERE pid = :pid", [':pid' => $data->nid])->fetchField();
        if (empty($cover_fid)) {
          // Skip albums with no images.
          continue;
        }
      }
      $albums[] = [
        'wid' => $data->wid,
        'nid' => $data->nid,
        'fid' => $cover_fid,
        'count' => $data->count,
        'title' => $data->title,
      ];
    }
    return $albums;
  }

  /**
   * Ajax callback to save new image order.
   */
  public function ajaxRearrange() {
    // @todo convert to CommandInterface class?
    $post_nid = $this->requestStack->getCurrentRequest()->request->get('pid');
    $post_uid = $this->requestStack->getCurrentRequest()->request->get('uid');
    $post_type = $this->requestStack->getCurrentRequest()->request->get('type');
    $post_order = $this->requestStack->getCurrentRequest()->request->get('order');
    $nid = $post_nid ?: 0;
    $uid = $post_uid ?: 0;
    $type = $post_type ?: 0;
    $new_order = $post_order ?: [];
    $message = '';
    if (!empty($new_order) && is_array($new_order)) {
      if ($type == 'images') {
        if ($nid) {
          $message = $this->editSortSave($new_order, $nid, $type);
        }
      }
      elseif ($type == 'albums') {
        if ($uid) {
          // Save sort order for albums.
          $message = $this->editSortAlbumsSave($new_order, $uid);
        }
      }
    }
    if ($nid) {
      // Clear album page cache.
      Cache::invalidateTags(['node:' . $nid, 'photos:album:' . $nid]);
    }

    // Build plain text response.
    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');
    $response->setContent($message);
    return $response;
  }

  /**
   * Save new order.
   */
  public function editSortSave($order = [], $nid = 0, $type = 'images') {
    if ($nid) {
      $access = FALSE;
      if ($nid) {
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        // Check for node_accss.
        $access = _photos_access('editAlbum', $node);
      }
      if ($access) {
        $weight = 0;
        // Update weight for all images in array / album.
        foreach ($order as $image_id) {
          $fid = str_replace('photos_', '', $image_id);
          if ($type == 'images') {
            // Save sort order for images in album.
            $this->connection->update('photos_image')
              ->fields([
                'wid' => $weight,
              ])
              ->condition('fid', $fid)
              ->condition('pid', $nid)
              ->execute();
          }
          $weight++;
        }
        if ($weight > 0) {
          $message = $this->t('Image order saved!');
          return $message;
        }
      }
    }
  }

  /**
   * Save new album weights.
   */
  public function editSortAlbumsSave($order = [], $uid = 0) {
    if ($uid) {
      $user = $this->currentUser();
      $access = FALSE;
      // @todo add support for admin role?
      if ($user->id() == $uid || $user->id() == 1) {
        $weight = 0;
        // Update weight for all albums in array.
        foreach ($order as $album_id) {
          $pid = str_replace('photos_', '', $album_id);
          $node = $this->entityTypeManager->getStorage('node')->load($pid);
          // Check for node_accss.
          $access = _photos_access('editAlbum', $node);
          if ($access) {
            $this->connection->update('photos_album')
              ->fields([
                'wid' => $weight,
              ])
              ->condition('pid', $pid)
              ->execute();
            $weight++;
          }
        }
        if ($weight > 0) {
          $message = $this->t('Album order saved!');
          return $message;
        }
      }
    }
  }

}
