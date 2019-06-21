<?php

namespace Drupal\photos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\photos\PhotosAlbum;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View albums and recent images.
 */
class PhotosController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(Connection $connection, RendererInterface $renderer) {
    $this->connection = $connection;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('renderer')
    );
  }

  /**
   * Album views.
   */
  public function albumViews($type, $limit, $url = 0, $uid = 0, $sort = ' n.nid DESC') {
    $query = $this->connection->select('photos_album', 'p');
    $query->join('node_field_data', 'n', 'n.nid = p.pid');
    $query->join('users_field_data', 'u', 'u.uid = n.uid');
    $query->fields('p', ['count', 'fid'])
      ->fields('n', ['nid', 'title'])
      ->fields('u', ['uid', 'name']);
    $query->condition('n.status', 1);

    if ($type == 'user') {
      $query->condition('n.uid', $uid);
    }
    if ($type == 'rand') {
      $query->orderRandom();
    }
    else {
      $query->orderBy('n.nid', 'DESC');
    }
    $query->range(0, $limit);
    $query->addTag('node_access');
    $results = $query->execute();

    $i = 0;
    foreach ($results as $result) {
      $photos_album = new PhotosAlbum($result->nid);
      $cover = $photos_album->getCover($result->fid);
      $view = '';
      if ($cover && isset($cover['view'])) {
        $view = $this->renderer->render($cover['view']);
      }
      $album[] = ['node' => $result, 'view' => $view];
      ++$i;
    }
    if ($i) {
      $photo_block = [
        '#theme' => 'photos_block',
        '#images' => $album,
        '#block_type' => 'album',
      ];
      $content = $this->renderer->render($photo_block);
      $url = Url::fromUri('base:' . $url);
      if ($url && $i >= $limit) {
        $more_link = [
          '#type' => 'more_link',
          '#url' => $url,
          '#title' => $this->t('View more'),
        ];
        $content .= $this->renderer->render($more_link);
      }
      if ($type == 'user') {
        return [
          'content' => $content,
          'title' => $this->t("@name's albums", ['@name' => $album[0]['node']->name]),
        ];
      }
      else {
        return $content;
      }
    }
  }

  /**
   * Returns an overview of recent albums and photos.
   *
   * @return array
   *   A render array for the photos_default theme.
   */
  public function contentOverview() {
    $account = $this->currentUser();
    $content = [];
    if ($account->id() && $account->hasPermission('create photo')) {
      $val = PhotosImage::blockView('user', 5, 'photos/image', $account->id());
      $content['user']['image'] = isset($val['content']) ? $val['content'] : '';
      $val = $this->albumViews('user', 5, 'photos/user/' . $account->id() . '/album', $account->id());
      $content['user']['album'] = $val['content'] ? $val['content'] : '';
    }
    $content['site']['image'] = PhotosImage::blockView('latest', 5, 'photos/image');
    $content['site']['album'] = $this->albumViews('latest', 5, 'photos/album');

    return [
      '#theme' => 'photos_default',
      '#content' => $content,
      '#empty' => $this->t('No photos available.'),
    ];
  }

}
