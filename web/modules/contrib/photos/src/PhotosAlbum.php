<?php

namespace Drupal\photos;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;

/**
 * Create an album object.
 */
class PhotosAlbum {

  /**
   * Album ID {node}.nid.
   *
   * @var int
   */
  protected $pid;

  /**
   * Constructs a PhotosAlbum object.
   *
   * @param int $nid
   *   Album ID {node}.nid.
   */
  public function __construct($nid) {
    $this->pid = $nid;
  }

  /**
   * Page and Teaser display settings.
   */
  public function nodeView($node, $display, $view_mode) {
    $album = [];
    $default_style = 'medium';
    if ($display <> 0) {
      $default_order = \Drupal::config('photos.settings')->get('photos_display_imageorder');
      $order = explode('|', (isset($node->album['imageorder']) ? $node->album['imageorder'] : $default_order));
      $order = PhotosAlbum::orderValueChange($order[0], $order[1]);
      $default_style = \Drupal::config('photos.settings')->get('photos_display_' . $view_mode . '_imagesize') ?: 'thumbnail';
      $style_name = isset($node->album[$view_mode . '_imagesize']) ? $node->album[$view_mode . '_imagesize'] : $default_style;
    }
    switch ($display) {
      case 0:
        // Display none.
        break;

      case 1:
        // Display cover.
        $album = [];
        if (isset($node->album['cover'])) {
          if (isset($node->album['cover']['view'])) {
            $album = $node->album['cover']['view'];
          }
          else {
            if (isset($node->album['cover']['uri'])) {
              $image = new \stdClass();
              $variables = [
                'style_name' => $style_name,
                'uri' => $node->album['cover']['uri'],
              ];

              // The image.factory service will check if our image is valid.
              $image_info = \Drupal::service('image.factory')->get($node->album['cover']['uri']);
              if ($image_info->isValid()) {
                $variables['width'] = $image_info->getWidth();
                $variables['height'] = $image_info->getHeight();
              }
              else {
                $variables['width'] = $variables['height'] = NULL;
              }

              $image_render_array = [
                '#theme' => 'image_style',
                '#uri' => $node->album['cover']['uri'],
                '#style_name' => $style_name,
              ];
              $image->view = $image_render_array;
              $image->href = 'photos/album/' . $node->id();
              $image->uri = $node->album['cover']['uri'];
              $image->pid = $node->id();
              $image->title = $node->getTitle();

              $image_render_array = [
                '#theme' => 'photos_image_html',
                '#image' => $image,
                '#style_name' => $style_name,
              ];
              $album = $image_render_array;
            }
          }
        }
        break;

      case 2:
        // Display thumbnails.
        $get_field = \Drupal::request()->query->get('field');
        $get_sort = \Drupal::request()->query->get('sort');
        $column = $get_field ? Html::escape($get_field) : 0;
        $sort = $get_sort ? Html::escape($get_sort) : 0;
        $view_num = \Drupal::config('photos.settings')->get('photos_display_' . $view_mode . '_viewnum') ?: 10;
        $limit = isset($node->album[$view_mode . '_viewnum']) ? $node->album[$view_mode . '_viewnum'] : $view_num;

        $term = PhotosAlbum::orderValue($column, $sort, $limit, $order);
        $db = \Drupal::database();
        $query = $db->select('file_managed', 'f');
        $query->join('photos_image', 'p', 'p.fid = f.fid');
        $query->fields('f', ['fid']);
        $query->condition('p.pid', $node->id());
        $query->orderBy($term['order']['column'], $term['order']['sort']);
        $query->range(0, $term['limit']);
        $result = $query->execute();

        $i = 0;
        // Necessary when upgrading from D6 to D7.
        $image_styles = image_style_options(FALSE);
        if (!isset($image_styles[$style_name])) {
          $style_name = \Drupal::config('photos.settings')->get('photos_display_teaser_imagesize');
        }
        $album = [];
        // Thumbnails.
        foreach ($result as $data) {
          $photos_image = new PhotosImage($data->fid);
          $variables = [
            'href' => 'photos/image/' . $data->fid,
          ];
          $album[] = $photos_image->view($style_name, $variables);
          ++$i;
        }
        break;

      case 3:
        // Get cover.
        $cover = FALSE;
        if (isset($node->album['cover']) && isset($node->album['cover']['uri'])) {
          $image_render_array = [
            '#theme' => 'image_style',
            '#style_name' => $style_name,
            '#uri' => $node->album['cover']['uri'],
            '#title' => $node->getTitle(),
            '#alt' => $node->getTitle(),
          ];
          $cover = $image_render_array;
        }

        if ($cover) {
          // Cover with colorbox gallery.
          $get_field = \Drupal::request()->query->get('field');
          $get_sort = \Drupal::request()->query->get('sort');
          $column = $get_field ? Html::escape($get_field) : 0;
          $sort = $get_sort ? Html::escape($get_sort) : 0;
          $view_num = \Drupal::config('photos.settings')->get('photos_display_' . $view_mode . '_viewnum') ?: 10;
          $limit = FALSE;

          // Query all images in gallery.
          $term = PhotosAlbum::orderValue($column, $sort, $limit, $order);
          $db = \Drupal::database();
          $query = $db->select('file_managed', 'f');
          $query->join('photos_image', 'p', 'p.fid = f.fid');
          $query->join('users_field_data', 'ufd', 'ufd.uid = f.uid');
          $query->fields('f', [
            'uri',
            'filemime',
            'created',
            'filename',
            'filesize',
          ])
            ->fields('p')
            ->fields('ufd', ['uid', 'name']);
          $query->condition('p.pid', $node->id());
          $query->orderBy($term['order']['column'], $term['order']['sort']);
          $result = $query->execute();

          $i = 0;
          // Setup colorbox.
          if (\Drupal::moduleHandler()->moduleExists('colorbox')) {
            $style = \Drupal::config('colorbox.settings')->get('custom.style');
            $album['#attached']['library'] = ['colorbox/colorbox', 'colorbox/' . $style];
            $colorbox_height = \Drupal::config('photos.settings')->get('photos_display_colorbox_max_height') ?: 100;
            $colorbox_width = \Drupal::config('photos.settings')->get('photos_display_colorbox_max_width') ?: 50;
            $js_settings = [
              'maxWidth' => $colorbox_width . '%',
              'maxHeight' => $colorbox_height . '%',
            ];
            $album['#attached']['drupalSettings']['colorbox'] = $js_settings;
          }
          // Display cover and list colorbox image links.
          foreach ($result as $data) {
            $style_name = isset($node->album['view_imagesize']) ? $node->album['view_imagesize'] : $style_name;
            $style = ImageStyle::load($style_name);
            $file_url = $style->buildUrl($data->uri);
            $image = NULL;
            if ($i == 0) {
              $image = $cover;
            }
            $album[] = [
              '#theme' => 'photos_image_colorbox_link',
              '#image' => $image,
              '#image_title' => $data->title,
              '#image_url' => $file_url,
              '#nid' => $node->id(),
            ];
            ++$i;
          }
        }
        break;
    }
    return $album;
  }

  /**
   * Get album cover.
   */
  public function getCover($fid = NULL, $title = '') {
    $pid = $this->pid;
    $cover = [];
    $image = FALSE;
    if (!$fid) {
      // Check album for cover fid.
      $db = \Drupal::database();
      $fid = $db->query("SELECT fid FROM {photos_album} WHERE pid = :pid", [':pid' => $pid])->fetchField();
    }
    if ($fid) {
      // Load image.
      $photos_image = new PhotosImage($fid);
      $image = $photos_image->load();
      if ($image) {
        // Prepare node album cover data.
        $cover['fid'] = $fid;
        $cover['uri'] = $image->uri;
        $style_name = \Drupal::config('photos.settings')->get('photos_cover_imagesize') ?: 'thumbnail';
        $image->href = Url::fromUri('base:photos/album/' . $pid)->toString();
        // Set alt and title to album node title.
        $image->alt = $title;
        $image->title = $title;
        $cover_view = [
          '#theme' => 'photos_image_html',
          '#image' => $image,
          '#style_name' => $style_name,
        ];
        $cover['view'] = $cover_view;
      }
    }

    if (!$fid || !$image) {
      // Cover not set, select an image from the album.
      $db = \Drupal::database();
      $query = $db->select('file_managed', 'f');
      $query->join('photos_image', 'p', 'p.fid = f.fid');
      $query->fields('f', ['fid']);
      $query->condition('p.pid', $pid);
      $fid = $query->execute()->fetchField();
      if ($fid) {
        return $this->getCover($fid);
      }
    }
    return $cover;
  }

  /**
   * Set album cover.
   */
  public function setCover($fid = 0) {
    $pid = $this->pid;
    // Update cover.
    $db = \Drupal::database();
    $db->update('photos_album')
      ->fields([
        'fid' => $fid,
      ])
      ->condition('pid', $pid)
      ->execute();
    // Clear node cache.
    Cache::invalidateTags(['node:' . $pid, 'photos:album:' . $pid]);
    \Drupal::messenger()->addMessage(t('Cover successfully set.'));
  }

  /**
   * Get album images.
   */
  public function getImages($limit = 10) {
    $images = [];
    // Prepare query.
    $get_field = \Drupal::request()->query->get('field');
    $column = $get_field ? Html::escape($get_field) : '';
    $get_sort = \Drupal::request()->query->get('sort');
    $sort = $get_sort ? Html::escape($get_sort) : '';
    $term = PhotosAlbum::orderValue($column, $sort, $limit, ['column' => 'p.wid', 'sort' => 'asc']);
    // Query images in this album.
    $db = \Drupal::database();
    $query = $db->select('file_managed', 'f')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('photos_image', 'p', 'p.fid = f.fid');
    $query->join('users_field_data', 'u', 'f.uid = u.uid');
    $query->join('node_field_data', 'n', 'n.nid = p.pid');
    $query->fields('f', ['fid']);
    $query->condition('p.pid', $this->pid);
    $query->limit($term['limit']);
    $query->orderBy($term['order']['column'], $term['order']['sort']);
    if ($term['order']['column'] <> 'f.fid') {
      $query->orderBy('f.fid', 'DESC');
    }
    $query->addTag('node_access');
    $results = $query->execute();
    // Prepare images.
    foreach ($results as $result) {
      $photos_image = new PhotosImage($result->fid);
      $images[] = $photos_image->load();
    }
    if (isset($images[0]->fid)) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($this->pid);
      $images[0]->info = [
        'pid' => $node->id(),
        'title' => $node->getTitle(),
        'uid' => $node->getOwnerId(),
      ];
      if (isset($node->album['cover'])) {
        $images[0]->info['cover'] = $node->album['cover'];
      }
    }
    return $images;
  }

  /**
   * Limit and sort by links.
   */
  public static function orderLinks($arg, $count = 0, $link = 0, $limit = 0) {
    // @todo move out? This is used in recent images, user images and album.
    // @todo move count and order links out.
    // Get current path.
    $q = \Drupal::service('path.current')->getPath();
    $field = [
      'weight' => t('By weight'),
      'created' => t('By time'),
      'comments' => t('By comments'),
      'filesize' => t('By filesize'),
    ];
    // Check count image views variable.
    $photos_image_count = \Drupal::config('photos.settings')->get('photos_image_count');
    if (!$photos_image_count) {
      $field['visits'] = t('By visits');
    }
    if ($limit) {
      $get_limit = \Drupal::request()->query->get('limit');
      $query = PhotosAlbum::getPagerQuery();
      $links['limit'] = '';
      if (!is_array($limit)) {
        $limit = [5, 10, 20, 30, 40, 50];
      }
      $limit_query = $query;
      $limit_count = count($limit);
      foreach ($limit as $key => $tt) {
        $limit_query['limit'] = $tt;
        $sort = [
          'query' => $limit_query,
          'attributes' => [
            'class' => [
              (isset($get_limit) && $get_limit == $tt) ? 'orderac' : NULL,
            ],
            'rel' => 'nofollow',
          ],
        ];

        $links['limit'] .= Link::fromTextAndUrl($tt, Url::fromUri('base:' . $q, $sort))->toString();
        if ($limit_count <> $key) {
          $links['limit'] .= ' ';
        }

      }
    }
    $links['count'] = $count;
    $links['link'] = $link ? $link : NULL;

    $sort_links = Link::fromTextAndUrl(t('Default'), Url::fromUri('base:' . $arg, ['attributes' => ['rel' => 'nofollow']]))->toString() . ' ';
    $sort_link_count = count($field);
    $get_field = \Drupal::request()->query->get('field');
    $get_limit = \Drupal::request()->query->get('limit');
    $get_sort = \Drupal::request()->query->get('sort');
    foreach ($field as $key => $t) {
      if (empty($get_field) || $get_field <> $key) {
        $sort = 'desc';
        $class = 'photos_order_desc';
      }
      elseif ($get_sort == 'desc') {
        $sort = 'asc';
        $class = 'photos_order_asc photos-active-sort';
      }
      else {
        $sort = 'desc';
        $class = 'photos_order_desc photos-active-sort';
      }
      $field_query = [
        'sort' => $sort,
        'field' => $key,
      ];
      if ($get_limit) {
        $field_query['limit'] = Html::escape($get_limit);
      }
      $sort_links .= Link::fromTextAndUrl($t, Url::fromUri('base:' . $q, [
        'query' => $field_query,
        'attributes' => [
          'class' => [$class],
          'rel' => 'nofollow',
        ],
      ]))->toString();
      if ($key <> $sort_link_count) {
        $sort_links .= ' ';
      }
    }
    if ($sort_links) {
      $links['sort'] = [
        '#markup' => $sort_links,
      ];
    }

    return [
      '#theme' => 'photos_album_links',
      '#links' => $links,
    ];
  }

  /**
   * Returns array of query parameters.
   */
  public static function getPagerQuery() {
    $query_array = ['limit', 'q', 'page', 'destination'];
    // @todo reivew and update as needed.
    $query = UrlHelper::filterQueryParameters($_REQUEST, array_merge($query_array, array_keys($_COOKIE)));

    return $query;
  }

  /**
   * Sort order labels.
   */
  public static function orderLabels() {
    return [
      'weight|asc' => t('Weight - smallest first'),
      'weight|desc' => t('Weight - largest first'),
      'created|desc' => t('Upload Date - newest first'),
      'created|asc' => t('Upload Date - oldest first'),
      'comments|desc' => t('Comments - most first'),
      'comments|asc' => t('Comments - least first'),
      'filesize|desc' => t('Filesize - smallest first'),
      'filesize|asc' => t('Filesize - largest first'),
      'visits|desc' => t('Visits - most first'),
      'visits|asc' => t('Visits - least first'),
    ];
  }

  /**
   * Extends photos order value.
   */
  public static function orderValueChange($field, $sort) {
    // @note timestamp is deprecated, but may exist
    // if albums are migrated from a previous version.
    $array = [
      'weight' => 'p.wid',
      'timestamp' => 'f.fid',
      'created' => 'f.fid',
      'comments' => 'p.comcount',
      'visits' => 'p.count',
      'filesize' => 'f.filesize',
    ];
    $array1 = [
      'desc' => 'desc',
      'asc' => 'asc',
    ];
    if (isset($array[$field]) && isset($array1[$sort])) {
      return [
        'column' => $array[$field],
        'sort' => $array1[$sort],
      ];
    }
    else {
      // Default if values not found.
      return [
        'column' => 'f.fid',
        'sort' => 'desc',
      ];
    }
  }

  /**
   * Query helper: sort order and limit.
   */
  public static function orderValue($field, $sort, $limit, $default = 0) {
    // @todo update default to check album default!
    if (!$field && !$sort) {
      $t['order'] = !$default ? ['column' => 'f.fid', 'sort' => 'desc'] : $default;
    }
    else {
      if (!$t['order'] = PhotosAlbum::orderValueChange($field, $sort)) {
        $t['order'] = !$default ? ['column' => 'f.fid', 'sort' => 'desc'] : $default;
      }
    }
    if ($limit) {
      $get_limit = \Drupal::request()->query->get('limit');
      if ($get_limit && !$show = intval($get_limit)) {
        $get_destination = \Drupal::request()->query->get('destination');
        if ($get_destination) {
          $str = $get_destination;
          if (preg_match('/.*limit=(\d*).*/i', $str, $mat)) {
            $show = intval($mat[1]);
          }
        }
      }
      $t['limit'] = isset($show) ? $show : $limit;
    }

    return $t;
  }

  /**
   * Return number of albums or photos.
   */
  public static function getCount($type, $id = 0) {
    $db = \Drupal::database();
    switch ($type) {
      case 'user_album':
      case 'user_image':
      case 'site_album':
      case 'site_image':
      case 'node_node':
        return $db->query("SELECT value FROM {photos_count} WHERE cid = :cid AND type = :type", [':cid' => $id, ':type' => $type])->fetchField();

      case 'node_album':
        return $db->query("SELECT count FROM {photos_album} WHERE pid = :pid", [':pid' => $id])->fetchField();
    }
  }

  /**
   * Update count.
   */
  public static function resetCount($cron = 0) {
    PhotosAlbum::setCount('site_album');
    PhotosAlbum::setCount('site_image');
    $time = $cron ? 7200 : 0;
    // @todo optimize. Check if new images since last count.
    $cron_last = \Drupal::state()->get('system.cron_last', 0);
    if ((\Drupal::time()->getRequestTime() - $cron_last) > $time) {
      $db = \Drupal::database();
      $result = $db->query('SELECT uid FROM {users} WHERE uid <> 0');
      foreach ($result as $t) {
        PhotosAlbum::setCount('user_image', $t->uid);
        PhotosAlbum::setCount('user_album', $t->uid);
      }
      $result = $db->query('SELECT pid FROM {photos_album}');
      foreach ($result as $t) {
        PhotosAlbum::setCount('node_album', $t->pid);
      }
      $result = $db->query('SELECT DISTINCT(nid) FROM {photos_node}');
      foreach ($result as $t) {
        PhotosAlbum::setCount('node_node', $t->nid);
      }
    }
  }

  /**
   * Calculate number of $type.
   */
  public static function setCount($type, $id = 0) {
    $db = \Drupal::database();
    $requestTime = \Drupal::time()->getRequestTime();
    switch ($type) {
      case 'user_image':
        $count = $db->query('SELECT count(p.fid) FROM {photos_image} p INNER JOIN {file_managed} f ON p.fid = f.fid WHERE f.uid = :id',
          [':id' => $id])->fetchField();
        $query = $db->update('photos_count');
        $query->fields([
          'value' => $count,
          'changed' => $requestTime,
        ]);
        $query->condition('cid', $id);
        $query->condition('type', $type);
        $affected_rows = $query->execute();
        if (!$affected_rows) {
          $db->insert('photos_count')
            ->fields([
              'cid' => $id,
              'changed' => $requestTime,
              'type' => $type,
              'value' => $count,
            ])
            ->execute();
        }
        // Clear cache tags.
        Cache::invalidateTags(['photos:image:user:' . $id]);
        break;

      case 'user_album':
        $count = $db->query('SELECT count(p.pid) FROM {photos_album} p INNER JOIN {node_field_data} n ON p.pid = n.nid WHERE n.uid = :uid',
          [':uid' => $id])->fetchField();
        $query = $db->update('photos_count')
          ->fields([
            'value' => $count,
            'changed' => $requestTime,
          ])
          ->condition('cid', $id)
          ->condition('type', $type);
        $affected_rows = $query->execute();
        if (!$affected_rows) {
          $db->insert('photos_count')
            ->fields([
              'cid' => $id,
              'changed' => $requestTime,
              'type' => $type,
              'value' => $count,
            ])
            ->execute();
        }
        // Clear cache tags.
        Cache::invalidateTags(['photos:album:user:' . $id]);
        break;

      case 'site_album':
        $count = $db->query('SELECT COUNT(pid) FROM {photos_album}')->fetchField();
        $query = $db->update('photos_count')
          ->fields([
            'value' => $count,
            'changed' => $requestTime,
          ])
          ->condition('cid', 0)
          ->condition('type', $type);
        $affected_rows = $query->execute();
        if (!$affected_rows) {
          $db->insert('photos_count')
            ->fields([
              'cid' => 0,
              'changed' => $requestTime,
              'type' => $type,
              'value' => $count,
            ])
            ->execute();
        }
        break;

      case 'site_image':
        $count = $db->query('SELECT COUNT(fid) FROM {photos_image}')->fetchField();
        $query = $db->update('photos_count')
          ->fields([
            'value' => $count,
            'changed' => $requestTime,
          ])
          ->condition('cid', 0)
          ->condition('type', $type);
        $affected_rows = $query->execute();
        if (!$affected_rows) {
          $db->insert('photos_count')
            ->fields([
              'cid' => 0,
              'changed' => $requestTime,
              'type' => $type,
              'value' => $count,
            ])
            ->execute();
        }
        // Clear cache tags.
        Cache::invalidateTags(['photos:image:recent']);
        break;

      case 'node_node':
        $count = $db->query('SELECT COUNT(nid) FROM {photos_node} WHERE nid = :nid', [':nid' => $id])->fetchField();
        $query = $db->update('photos_count')
          ->fields([
            'value' => $count,
            'changed' => $requestTime,
          ])
          ->condition('cid', $id)
          ->condition('type', $type);
        $affected_rows = $query->execute();
        if (!$affected_rows) {
          $db->insert('photos_count')
            ->fields([
              'cid' => $id,
              'changed' => $requestTime,
              'type' => $type,
              'value' => $count,
            ])
            ->execute();
        }
        break;

      case 'node_album':
        $count = $db->query("SELECT COUNT(fid) FROM {photos_image} WHERE pid = :pid", [':pid' => $id])->fetchField();
        $db->update('photos_album')
          ->fields([
            'count' => $count,
          ])
          ->condition('pid', $id)
          ->execute();
        break;
    }
  }

  /**
   * Tracks number of albums created and number of albums allowed.
   */
  public static function userAlbumCount() {
    $user = \Drupal::currentUser();
    $user_roles = $user->getRoles();
    $t['create'] = PhotosAlbum::getCount('user_album', $user->id());
    // @todo upgrade path? Check D7 role id and convert pnum variables as needed.
    $role_limit = 0;
    $t['total'] = 20;
    // Check highest role limit.
    foreach ($user_roles as $role) {
      if (\Drupal::config('photos.settings')->get('photos_pnum_' . $role)
        && \Drupal::config('photos.settings')->get('photos_pnum_' . $role) > $role_limit) {
        $role_limit = \Drupal::config('photos.settings')->get('photos_pnum_' . $role);
      }
    }
    if ($role_limit > 0) {
      $t['total'] = $role_limit;
    }

    $t['remain'] = ($t['total'] - $t['create']);
    if ($user->id() <> 1 && $t['remain'] <= 0) {
      $t['rest'] = 1;
    }
    return $t;
  }

  /**
   * User albums.
   */
  public static function userAlbumOptions($uid = 0, $current = 0) {
    if (!$uid) {
      $uid = \Drupal::currentUser()->id();
    }
    $output = [];

    // Query user albums.
    $db = \Drupal::database();
    $query = $db->select('node_field_data', 'n');
    $query->join('photos_album', 'a', 'a.pid = n.nid');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.uid', $uid);
    $query->orderBy('n.nid', 'DESC');
    $result = $query->execute();

    $true = FALSE;
    foreach ($result as $a) {
      $choice = new \stdClass();
      $choice->option = [$a->nid => $a->title];
      $output[$a->nid] = $choice;
      $true = TRUE;
    }
    if ($current) {
      $choice = new \stdClass();
      $choice->option = [$current[0] => $current[1]];
      $output[$a->nid] = $choice;
    }
    if (!$true) {
      $output = [t('You do not have an album yet.')];
    }

    return $output;
  }

}
