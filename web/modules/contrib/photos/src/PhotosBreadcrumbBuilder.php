<?php

namespace Drupal\photos;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Photos breadcrumb builder.
 */
class PhotosBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the PathBasedBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(Connection $connection, RequestContext $context, EntityManagerInterface $entity_manager) {
    $this->connection = $connection;
    $this->context = $context;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Check if image page.
    $fid = $route_match->getParameter('file');
    if ($fid) {
      $path = trim($this->context->getPathInfo(), '/');
      $path_elements = explode('/', $path);
      return ($path_elements[0] == 'photos' && $path_elements[1] == 'image');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);
    // Home.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $fid = $route_match->getParameter('file');
    if ($fid) {
      // Recent images.
      $breadcrumb->addLink(Link::createFromRoute($this->t('Images'), 'photos.image.recent'));
      // Images by User.
      $uid = $this->connection->query("SELECT uid FROM {file_managed} WHERE fid = :fid", [':fid' => $fid])->fetchField();
      $account = $this->entityManager->getStorage('user')->load($uid);
      $username = $account->getUsername();
      $breadcrumb->addLink(Link::createFromRoute($this->t('Images by :name', [':name' => $username]), 'photos.user.images', ['user' => $uid]));
      // Album.
      $pid = $this->connection->query("SELECT pid FROM {photos_image} WHERE fid = :fid", [':fid' => $fid])->fetchField();
      $node = $this->entityManager->getStorage('node')->load($pid);
      $breadcrumb->addLink(Link::createFromRoute($node->getTitle(), 'photos.album', ['node' => $pid]));
    }

    return $breadcrumb;
  }

}
