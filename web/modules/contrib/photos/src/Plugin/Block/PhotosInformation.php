<?php

namespace Drupal\photos\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Photo information' block.
 *
 * @Block(
 *   id = "photos_information",
 *   admin_label = @Translation("Photo Information"),
 *   category = @Translation("Photos")
 * )
 */
class PhotosInformation extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
   * Constructs a new BookNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, RequestStack $request_stack, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->connection = $connection;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    // Check if user can view photos.
    if ($account->hasPermission('view photo')) {
      $access = AccessResult::allowed();
    }
    else {
      $access = AccessResult::forbidden();
    }
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = [];

    // Check which pager to load.
    $fid = $this->routeMatch->getParameter('file');
    $current_path = trim($this->routeMatch->getRouteObject()->getPath(), '/');
    $arg = explode('/', $current_path);
    $pager_type = 'pid';
    if (isset($arg[2])) {
      // @todo cleanup sub-albums were removed.
      $get_photos_sub = $this->requestStack->getCurrentRequest()->query->get('photos_sub');
      if ($arg[0] == 'photos' && $arg[1] == 'image' && !empty($fid) && $get_photos_sub) {
        $pager_type = 'sub';
        $pager_id = (int) $get_photos_sub;
      }
      elseif ($arg[0] == 'photos' && $arg[1] == 'image' && !empty($fid)) {
        $pager_type = 'pid';
      }
    }

    if (isset($fid) && !empty($fid)) {
      // Get current image.
      $query = $this->connection->select('photos_image', 'p');
      $query->join('file_managed', 'f', 'f.fid = p.fid');
      $query->join('node_field_data', 'n', 'n.nid = p.pid');
      $query->join('users_field_data', 'u', 'f.uid = u.uid');
      $query->fields('p', ['count', 'comcount', 'exif', 'des'])
        ->fields('f', ['uri', 'created', 'filemime', 'fid'])
        ->fields('n', ['nid', 'title'])
        ->fields('u', ['name', 'uid'])
        ->condition('p.fid', $fid);
      $query->addTag('node_access');
      $image = $query->execute()->fetchObject();
      if ($image) {
        if ($pager_type == 'pid') {
          $pager_id = $image->nid;
        }
        // Get pager image(s).
        $photos_image = new PhotosImage($fid);
        $image->pager = $photos_image->pager($pager_id, $pager_type);

        $content = [
          '#theme' => 'photos_image_block',
          '#image' => $image,
          '#cache' => [
            'max_age' => 0,
          ],
        ];
        $content['#attached']['library'][] = 'photos/photos.block.information';
      }
      return $content;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
