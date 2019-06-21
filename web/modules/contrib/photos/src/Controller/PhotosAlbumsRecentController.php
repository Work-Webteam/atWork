<?php

namespace Drupal\photos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View recent albums.
 */
class PhotosAlbumsRecentController extends ControllerBase {

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
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(Connection $connection, EntityManagerInterface $entity_manager, RendererInterface $renderer) {
    $this->connection = $connection;
    $this->entityManager = $entity_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Returns content for recent albums.
   *
   * @return array
   *   An array containing markup for the page content.
   */
  public function contentOverview() {
    // @todo a lot of duplicate code can be consolidated in these controllers.
    $query = $this->connection->select('node', 'n')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('photos_album', 'p', 'p.pid = n.nid');
    $query->fields('n', ['nid']);
    $query->orderBy('n.nid', 'DESC');
    $query->limit(10);
    $query->addTag('node_access');
    $results = $query->execute();

    $output = '';
    foreach ($results as $result) {
      $node = $this->entityManager->getStorage('node')->load($result->nid);
      $node_view = node_view($node, 'full');
      $output .= $this->renderer->render($node_view);
    }
    if ($output) {
      $pager = ['#type' => 'pager'];
      $output .= $this->renderer->render($pager);
    }
    else {
      $output .= $this->t('No albums have been created yet.');
    }

    return [
      '#markup' => $output,
    ];
  }

}
