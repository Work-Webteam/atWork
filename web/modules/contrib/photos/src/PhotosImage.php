<?php

namespace Drupal\photos;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Create images object.
 */
class PhotosImage {

  /**
   * The {file_managed}.fid.
   *
   * @var int
   */
  protected $fid;

  /**
   * Constructs a PhotosImage object.
   *
   * @param int $fid
   *   Fild ID {file_managed}.fid.
   */
  public function __construct($fid) {
    $this->fid = $fid;
  }

  /**
   * Load image file and album data.
   */
  public function load() {
    $fid = $this->fid;
    // Query image data.
    // @todo check access. Is ->addTag('node_access') needed here? If so, rewrite query.
    //   - I think access is already checked before we get here.
    $db = \Drupal::database();
    $image = $db->query('SELECT f.fid, f.uri, f.filemime, f.created, f.filename, n.title as node_title, a.data, u.uid, u.name, p.*
      FROM {file_managed} f
      INNER JOIN {photos_image} p ON f.fid = p.fid
      INNER JOIN {node_field_data} n ON p.pid = n.nid
      INNER JOIN {photos_album} a ON a.pid = n.nid
      INNER JOIN {users_field_data} u ON f.uid = u.uid
      WHERE p.fid = :fid', [':fid' => $fid])->fetchObject();
    // Set image height and width.
    if (!isset($image->height) && isset($image->uri)) {
      // The image.factory service will check if our image is valid.
      $image_info = \Drupal::service('image.factory')->get($image->uri);
      if ($image_info->isValid()) {
        $image->width = $image_info->getWidth();
        $image->height = $image_info->getHeight();
      }
      else {
        $image->width = $image->height = NULL;
      }
    }
    return $image;
  }

  /**
   * Return render array to view image.
   *
   * @param string $style_name
   *   The image style machine name.
   * @param array $variables
   *   (Optional) variables to override image defaults:
   *   - 'title': image title and alt if alt is empty.
   *   - 'href': image link href.
   *
   * @return array
   *   Render array for image view.
   */
  public function view($style_name = NULL, array $variables = []) {
    $image = $this->load();
    if (isset($variables['title'])) {
      $image->title = $variables['title'];
    }
    if (!$style_name) {
      // Get thumbnail image style from admin settings.
      $image_sizes = \Drupal::config('photos.settings')->get('photos_size');
      $style_name = key($image_sizes);
    }
    if (!$style_name) {
      // Fallback on default thumbnail style.
      $style_name = 'thumbnail';
    }
    if (isset($variables['href'])) {
      $image->href = $variables['href'];
    }
    // Check scheme and prep image.
    $scheme = file_uri_scheme($image->uri);
    $uri = $image->uri;
    // If private create temporary derivative.
    if ($scheme == 'private') {
      $photos_image = new PhotosImage($image->fid);
      $url = $photos_image->derivative($uri, $style_name, $scheme);
    }
    else {
      // Public and all other images.
      $style = ImageStyle::load($style_name);
      $url = $style->buildUrl($uri);
    }
    // Build image render array.
    $title = isset($image->title) ? $image->title : '';
    $alt = isset($image->alt) ? $image->alt : $title;
    $image_render_array = [
      '#theme' => 'image',
      '#uri' => $url,
      '#alt' => $alt,
      '#title' => $title,
    ];

    return $image_render_array;
  }

  /**
   * Generate image style derivatives and return image file URL.
   *
   * Originally added to create private image style derivatives.
   */
  public function derivative($uri, $style_name, $scheme = 'private') {
    // Load the image style configuration entity.
    $style = ImageStyle::load($style_name);

    // Create URI with fid_{fid}.
    $pathinfo = pathinfo($uri);
    $ext = strtolower($pathinfo['extension']);
    // Set temporary file destination.
    $destination = $scheme . '://photos/tmp_images/' . $style_name . '/image_' . $this->fid . '.' . $ext;
    // Create image file.
    $style->createDerivative($uri, $destination);

    // Return URL.
    $url = file_create_url($destination);
    return $url;
  }

  /**
   * Return URL to image file.
   *
   * @note this is not currently in use.
   */
  public function url($uri, $style_name = 'thumbnail') {
    $image_url = '';
    if ($style_name == 'original') {
      $image_styles = image_style_options(FALSE);
      if (isset($image_styles['original'])) {
        $image_url = ImageStyle::load($style_name)->buildUrl($uri);
      }
      else {
        $image_url = file_create_url($uri);
      }
    }
    else {
      $image_url = ImageStyle::load($style_name)->buildUrl($uri);
    }

    return $image_url;
  }

  /**
   * Delete image.
   */
  public function delete($filepath = NULL, $count = FALSE) {
    $fid = $this->fid;
    if (!$filepath) {
      if ($count) {
        $file = File::load($fid);
        $db = \Drupal::database();
        $file->pid = $db->select('photos_image', 'p')
          ->fields('p', ['pid'])
          ->condition('fid', $fid)
          ->execute()->fetchField();
        $filepath = $file->getFileUri();
      }
      else {
        $db = \Drupal::database();
        $filepath = $db->query('SELECT uri FROM {file_managed} WHERE fid = :fid', [':fid' => $fid])->fetchField();
      }
    }
    if ($filepath) {
      if (\Drupal::config('photos.settings')->get('photos_comment')) {
        $db = \Drupal::database();
        $result = $db->select('photos_comment', 'v')
          ->fields('v', ['cid'])
          ->condition('v.fid', $fid)
          ->execute();
        $cids = $result->fetchAssoc();
        if ($cids) {
          $comments = \Drupal::entityManager()->getStorage('comment')->loadMultiple($cids);
          foreach ($comments as $comment) {
            // Delete comment.
            $comment->delete();
          }
        }
      }
      // If photos_access is enabled.
      if (\Drupal::config('photos.settings')->get('photos_access_photos')) {
        $file_scheme = \Drupal::service('file_system')->uriScheme($filepath);
        if ($file_scheme == 'private') {
          // Delete private image styles.
          $pathinfo = pathinfo($filepath);
          $ext = strtolower($pathinfo['extension']);
          $basename = 'image_' . $fid . '.' . $ext;
          // Find all derivatives for this image.
          $file_uris = file_scan_directory('private://photos/tmp_images', '~\b' . $basename . '\b~');
          foreach ($file_uris as $uri => $data) {
            // Delete.
            file_unmanaged_delete($uri);
          }
        }
      }
      $db = \Drupal::database();
      $db->delete('photos_image')
        ->condition('fid', $fid)
        ->execute();
      $db->delete('photos_node')
        ->condition('fid', $fid)
        ->execute();
      $db->delete('photos_comment')
        ->condition('fid', $fid)
        ->execute();
      if ($count) {
        // Update image count.
        PhotosAlbum::setCount('node_node', $file->pid);
        PhotosAlbum::setCount('node_album', $file->pid);
        PhotosAlbum::setCount('user_image', $file->getOwnerId());
        // Update comment statistics for album node.
        // @todo Argument 1 passed to Drupal\comment\CommentStatistics::update() must be an instance of Drupal\comment\CommentInterface.
        // _comment_update_node_statistics($file->pid);
        // $node = \Drupal\node\Entity\Node::load($file->pid);
        // \Drupal::service('comment.statistics')->update($node);
        // @todo delete comments.
      }

      if (empty($file)) {
        $file = File::load($fid);
      }
      if (empty($file->pid)) {
        $db = \Drupal::database();
        $file->pid = $db->select('photos_image', 'p')
          ->fields('p', ['pid'])
          ->condition('fid', $file->id())
          ->execute()->fetchField();
      }
      // Delete file usage and delete files.
      $file_usage = \Drupal::service('file.usage');
      $file_usage->delete($file, 'photos', 'node', $file->pid);
      file_delete($file->id());
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Photos image view pager block.
   */
  public function pager($id, $type = 'pid') {
    $fid = $this->fid;
    $db = \Drupal::database();
    $query = $db->select('file_managed', 'f');
    $query->join('photos_image', 'p', 'f.fid = p.fid');
    $query->fields('p', ['pid'])
      ->fields('f', ['fid', 'uri', 'filename']);

    // Default order by fid.
    $order = ['column' => 'f.fid', 'sort' => 'DESC'];
    if ($type == 'pid') {
      // Viewing album.
      // Order images by album settings.
      $db = \Drupal::database();
      $album_data = $db->query('SELECT data FROM {photos_album} WHERE pid = :pid', [':pid' => $id])->fetchField();
      $album_data = unserialize($album_data);
      $default_order = \Drupal::config('photos.settings')->get('photos_display_imageorder');
      $image_order = isset($album_data['imageorder']) ? $album_data['imageorder'] : $default_order;
      $order = explode('|', $image_order);
      $order = PhotosAlbum::orderValueChange($order[0], $order[1]);
      $query->condition('p.pid', $id);
    }
    elseif ($type == 'uid') {
      // Viewing all user images.
      $query->condition('f.uid', $id);
    }
    else {
      // Show all.
    }
    $query->orderBy($order['column'], $order['sort']);
    if ($order['column'] <> 'f.fid') {
      $query->orderBy('f.fid', 'DESC');
    }
    $result = $query->execute();

    $style_name = \Drupal::config('photos.settings')->get('photos_pager_imagesize');
    $stop = $t['prev'] = $t['next'] = 0;
    $num = 0;
    foreach ($result as $image) {
      $num++;
      $image_view = '';
      if (isset($image->fid)) {
        $photos_image = new PhotosImage($image->fid);
        $image_view = $photos_image->view($style_name);
      }
      if ($stop == 1) {
        $t['next_view'] = $image_view;
        // Next image.
        $t['next_url'] = Url::fromUri('base:photos/image/' . $image->fid)->toString();
        break;
      }
      if ($image->fid == $fid) {
        $t['current_view'] = $image_view;
        $stop = 1;
      }
      else {
        $t['prev'] = $image;
        $t['prev']->view = $image_view;
      }
    }
    if ($t['prev']) {
      $t['prev_view'] = $t['prev']->view;
      // Previous image.
      $t['prev_url'] = Url::fromUri('base:photos/image/' . $t['prev']->fid)->toString();
    }

    return $t;
  }

  /**
   * Comments on single picture.
   */
  public function comments($com_count, $node) {
    $fid = $this->fid;
    $output = [];
    if (\Drupal::moduleHandler()->moduleExists('comment') && \Drupal::currentUser()->hasPermission('access comments')) {
      // @todo get other comment form if needed?
      if ($com_count && ($node->comment && $node->comment->status <> 0 || $node->comment_photos && $node->comment_photos->status <> 0)
        || \Drupal::currentUser()->hasPermission('administer comments')) {

        // @todo look up setting for photos comment field.
        $mode = CommentManagerInterface::COMMENT_MODE_THREADED;

        // @todo look up setting for photos comment field.
        $comments_per_page = 50;

        $db = \Drupal::database();
        $query = $db->select('photos_comment', 'v')->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $query->join('comment_field_data', 'c', 'c.cid = v.cid');
        $query->addField('v', 'cid');
        $query->condition('v.fid', $fid)
          ->addTag('node_access')
          ->addTag('comment_filter')
          ->addMetaData('base_table', 'node')
          ->limit($comments_per_page);

        $db = \Drupal::database();
        $count_query = $db->select('photos_comment', 'v');
        $count_query->join('comment_field_data', 'c', 'c.cid = v.cid');
        $count_query->addExpression('COUNT(*)');
        $count_query
          ->condition('v.fid', $fid)
          ->addTag('node_access')
          ->addTag('comment_filter')
          ->addMetaData('base_table', 'node');

        if (!\Drupal::currentUser()->hasPermission('administer comments')) {
          $query->condition('c.status', CommentInterface::PUBLISHED);
          $count_query->condition('c.status', CommentInterface::PUBLISHED);
        }

        if ($mode === CommentManagerInterface::COMMENT_MODE_FLAT) {
          $query->orderBy('c.cid', 'ASC');
        }
        else {
          $query->addExpression('SUBSTRING(c.thread, 1, (LENGTH(c.thread) - 1))', 'torder');
          $query->orderBy('torder', 'ASC');
        }
        $query->setCountQuery($count_query);
        $cids = $query->execute()->fetchCol();

        if (!empty($cids)) {
          $comments = \Drupal::entityManager()->getStorage('comment')->loadMultiple($cids);
          // comment_prepare_thread($comments);
          $build = \Drupal::entityManager()->getViewBuilder('comment')->viewMultiple($comments);
          $build['pager']['#type'] = 'pager';
          $output['comments'] = $build;
        }
      }

      if ($node->comment && $node->comment->status == 2 || $node->comment_photos && $node->comment_photos->status == 2) {
        $field_name = 'comment';
        // @todo get other comment form if needed?
        if ($node->comment_photos) {
          $field_name = 'comment_photos';
        }
        if (\Drupal::currentUser()->hasPermission('post comments') && \Drupal::config('photos.settings')->get('photos_comment')
          || \Drupal::currentUser()->hasPermission('administer comments') && \Drupal::config('photos.settings')->get('photos_comment')) {

          // Prep comment form.
          $definition = \Drupal::entityTypeManager()->getDefinition('comment');
          $bundle_key = $definition->get('entity_keys')['bundle'];
          $values = [
            'entity_type' => 'node',
            'field_name' => $field_name,
            $bundle_key => 'comment',
            'entity_id' => [
              'target_id' => $node->id(),
            ],
          ];
          // Build comment entity for form.
          $entity = \Drupal::entityTypeManager()->getStorage($definition->get('id'))
            ->create($values);

          // Get entity form.
          $build = \Drupal::service('entity.form_builder')->getForm($entity);
          $output['comment_form'] = $build;
        }
      }
    }

    if ($output) {
      if (\Drupal::moduleHandler()->moduleExists('ajax_comments')) {
        // Add support for ajax comments on image page.
        $output['comments']['#prefix'] = '<div id="comment-wrapper-nid-' . $node->id() . '">';
        $output['comments']['#prefix'] .= '<div class="ajax-comment-wrapper"></div>';
        $output['comments']['#suffix'] = '</div>';
      }
    }

    return $output;
  }

  /**
   * Image size select options.
   */
  public static function sizeOptions($none = 0) {
    if ($none) {
      $v[] = 'Do not show';
    }
    $info = PhotosImage::sizeInfo();
    $v = $info['size'];

    return $v;
  }

  /**
   * Storage process information.
   */
  public static function sizeInfo() {
    $info = \Drupal::config('photos.settings')->get('photos_size');
    if (is_array($info)) {
      $v['count'] = count($info);
      $v['size'] = $info;
      return $v;
    }

    return FALSE;
  }

  /**
   * Extends image block view(s).
   */
  public static function blockView($type, $limit, $url = 'photos/image', $uid = 0, $sort = ['column' => 'f.fid', 'order' => 'DESC']) {
    $db = \Drupal::database();
    $query = $db->select('file_managed', 'f');
    $query->join('photos_image', 'p', 'p.fid = f.fid');
    $query->join('node_field_data', 'n', 'n.nid = p.pid');
    $query->join('users_field_data', 'u', 'u.uid = f.uid');
    $query->fields('f', ['fid']);
    $query->condition('n.status', 1);

    if ($type == 'user') {
      $query->condition('f.uid', $uid);
    }

    if ($type == 'rand') {
      $query->orderRandom();
    }
    else {
      $query->orderBy($sort['column'], $sort['order']);
    }
    $query->range(0, $limit);
    $query->addTag('node_access');
    $results = $query->execute();

    $images = [];
    foreach ($results as $result) {
      $photos_image = new PhotosImage($result->fid);
      $image = $photos_image->load();
      $image->href = Url::fromUri('base:' . $url . '/' . $image->fid);
      $images[] = $image;
    }
    if (isset($images[0]->fid)) {
      $render_array = [
        '#theme' => 'photos_block',
        '#images' => $images,
        '#block_type' => 'image',
      ];
      // @todo use renderer.
      $content = drupal_render($render_array);

      if ($url && count($images) >= $limit) {
        $more_link = [
          '#type' => 'more_link',
          '#url' => Url::fromUri('base:' . $url),
          '#title' => t('View more'),
        ];
        // @todo use renderer.
        $content .= drupal_render($more_link);
      }
      if ($type == 'user') {
        return [
          'content' => $content,
          'title' => t("@name's images", ['@name' => $images[0]->name]),
        ];
      }
      else {
        return $content;
      }
    }
    return FALSE;
  }

}
