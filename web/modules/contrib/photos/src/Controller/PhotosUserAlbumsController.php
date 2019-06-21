<?php

namespace Drupal\photos\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Display user albums.
 */
class PhotosUserAlbumsController extends ControllerBase {

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(Connection $connection, EntityManagerInterface $entity_manager, RendererInterface $renderer, RouteMatchInterface $route_match) {
    $this->connection = $connection;
    $this->entityManager = $entity_manager;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('renderer'),
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
    $account = $this->entityManager->getStorage('user')->load($uid);
    if (!$account || _photos_access('viewUser', $account)) {
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
      $account = $this->entityManager->getStorage('user')->load($uid);
      return $this->t("@name's albums", ['@name' => $account->getUsername()]);
    }
    else {
      return $this->t('My Albums');
    }
  }

  /**
   * Returns content for user albums.
   *
   * @return array
   *   An array of markup for user album content.
   */
  public function contentOverview() {
    // Get current user and account.
    // @todo a lot of duplicate code can be consolidated in these controllers.
    $user = $this->currentUser();
    $uid = $this->routeMatch->getParameter('user');
    $account = FALSE;
    if ($uid && is_numeric($uid)) {
      $account = $this->entityManager->getStorage('user')->load($uid);
    }
    if (!$account) {
      throw new NotFoundHttpException();
    }

    $output = '';
    $build = [];
    $cache_tags = ['user:' . $uid];
    if ($account->id() && $account->id() <> 0) {
      if ($user->id() == $account->id()) {
        $output = Link::fromTextAndUrl($this->t('Rearrange albums'), Url::fromRoute('photos.album.rearrange', ['user' => $account->id()]))->toString();
      }
      $query = $this->connection->select('node_field_data', 'n')
        ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $query->join('photos_album', 'p', 'p.pid = n.nid');
      $query->fields('n', ['nid']);
      $query->condition('n.uid', $account->id());
      $query->orderBy('p.wid', 'ASC');
      $query->orderBy('n.nid', 'DESC');
      $query->limit(10);
      $query->addTag('node_access');
      $results = $query->execute();
    }
    else {
      $query = $this->connection->select('node', 'n')
        ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $query->join('photos_album', 'p', 'p.pid = n.nid');
      $query->fields('n', ['nid']);
      $query->orderBy('n.nid', 'DESC');
      $query->limit(10);
      $query->addTag('node_access');
      $results = $query->execute();
    }
    foreach ($results as $result) {
      $nid = $result->nid;
      $cache_tags[] = 'node:' . $nid;
      $cache_tags[] = 'photos:album:' . $nid;
      $node = $this->entityManager->getStorage('node')->load($result->nid);
      $node_view = node_view($node, 'full');
      $output .= $this->renderer->render($node_view);
    }
    if ($output) {
      $pager = ['#type' => 'pager'];
      $output .= $this->renderer->render($pager);
    }
    else {
      if ($account <> FALSE) {
        $output .= $this->t('@name has not created an album yet.', ['@name' => $account->getUsername()]);
      }
      else {
        $output .= $this->t('No albums have been created yet.');
      }
    }
    $build['#markup'] = $output;
    $build['#cache'] = [
      'tags' => $cache_tags,
    ];

    return $build;
  }

}
