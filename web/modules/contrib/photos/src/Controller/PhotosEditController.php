<?php

namespace Drupal\photos\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\photos\PhotosAlbum;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Edit images and image details.
 */
class PhotosEditController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The FormBuilder object.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(Connection $connection, CurrentPathStack $current_path, FormBuilderInterface $form_builder, ModuleHandlerInterface $module_handler, RendererInterface $renderer, RequestStack $request_stack, RouteMatchInterface $route_match) {
    $this->connection = $connection;
    $this->currentPath = $current_path;
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
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
      $container->get('path.current'),
      $container->get('form_builder'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('request_stack'),
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
    // Check for available parameters.
    $node = $this->routeMatch->getParameter('node');
    $fid = $this->routeMatch->getParameter('file');
    if ($node && $fid) {
      $nid = $node->id();
      // Update cover.
      if (_photos_access('editAlbum', $node)) {
        // Allowed to update album cover image.
        return AccessResult::allowed();
      }
      else {
        // Deny access.
        return AccessResult::forbidden();
      }
    }
    elseif ($fid) {
      // Check if edit or delete.
      $current_path = $this->currentPath->getPath();
      $path_args = explode('/', $current_path);
      if (isset($path_args[4])) {
        if ($path_args[4] == 'edit') {
          if (_photos_access('imageEdit', $fid)) {
            // Allowed to edit image.
            return AccessResult::allowed();
          }
        }
        elseif ($path_args[4] == 'delete') {
          if (_photos_access('imageDelete', $fid)) {
            // Allowed to delete image.
            return AccessResult::allowed();
          }
        }
      }
      // Deny access.
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::neutral();
    }
  }

  /**
   * Edit image.
   */
  public function editImage($file) {
    $fid = $file;
    $query = $this->connection->select('file_managed', 'f');
    $query->join('photos_image', 'p', 'p.fid = f.fid');
    $query->join('users_field_data', 'u', 'f.uid = u.uid');
    $query->fields('f', ['uri', 'filemime', 'created', 'filename', 'filesize']);
    $query->fields('p');
    $query->fields('u', ['uid', 'name']);
    $query->condition('f.fid', $fid);
    $image = $query->execute()->fetchObject();

    if ($image && isset($image->fid)) {
      $edit_form = $this->formBuilder->getForm('\Drupal\photos\Form\PhotosImageEditForm', $image);
      return $edit_form;
    }
    else {
      throw new NotFoundHttpException();
    }

  }

  /**
   * Delete image from gallery and site.
   */
  public function deleteImage($file) {
    $fid = $file;
    $confirm_delete_form = $this->formBuilder->getForm('\Drupal\photos\Form\PhotosImageConfirmDeleteForm', $fid);
    if ($this->moduleHandler->moduleExists('colorbox_load')) {
      // Dispaly form in modal popup.
      // @todo does this still work? Test colorbox_load module.
      print $this->renderer->render($confirm_delete_form);
    }
    else {
      // Render full page.
      return $confirm_delete_form;
    }
  }

  /**
   * Ajax edit image.
   */
  public function ajaxEditUpdate($fid = NULL) {
    $message = '';
    $post_id = $this->requestStack->getCurrentRequest()->request->get('id');
    if ($post_id) {
      $post_value = $this->requestStack->getCurrentRequest()->request->get('value');
      $value = $post_value ? trim($post_value) : '';
      $id = Html::escape($post_id);
      // Get fid.
      if (strstr($id, 'title')) {
        $switch = 'title';
        $fid = str_replace('photos-image-edit-title-', '', $id);
      }
      elseif (strstr($id, 'des')) {
        $switch = 'des';
        $fid = str_replace('photos-image-edit-des-', '', $id);
      }
      $fid = filter_var($fid, FILTER_SANITIZE_NUMBER_INT);
      // Check user image edit permissions.
      // @todo photos.routing.yml _csrf_token: 'TRUE'.
      if ($fid && _photos_access('imageEdit', $fid)) {
        switch ($switch) {
          case 'title':
            $this->connection->update('photos_image')
              ->fields([
                'title' => $value,
              ])
              ->condition('fid', $fid)
              ->execute();
            $message = Html::escape($value);
            break;

          case 'des':
            $this->connection->update('photos_image')
              ->fields([
                'des' => $value,
              ])
              ->condition('fid', $fid)
              ->execute();
            $message = Html::escape($value);
            break;
        }
        // Clear cache.
        $pid = $this->connection->query("SELECT pid FROM {photos_image} WHERE fid = :fid", [':fid' => $fid])->fetchField();
        if ($pid) {
          Cache::invalidateTags(['node:' . $pid, 'photos:album:' . $pid]);
        }
        Cache::invalidateTags(['photos:image:' . $fid]);
      }
    }

    // Build plain text response.
    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');
    $response->setContent($message);
    return $response;
  }

  /**
   * Ajax edit image load text.
   */
  public function ajaxEditUpdateLoad() {
    $message = '';
    $post_id = $this->requestStack->getCurrentRequest()->request->get('id');
    if ($post_id) {
      $id = Html::escape($post_id);
      if (strstr($id, 'title')) {
        $switch = 'title';
        $fid = str_replace('photos-image-edit-title-', '', $id);
      }
      elseif (strstr($id, 'des')) {
        $switch = 'des';
        $fid = str_replace('photos-image-edit-des-', '', $id);
      }
      $fid = filter_var($fid, FILTER_SANITIZE_NUMBER_INT);
      // Check user image edit permissions.
      // @todo photos.routing.yml _csrf_token: 'TRUE'.
      if ($fid && _photos_access('imageEdit', $fid)) {
        switch ($switch) {
          case 'title':
            $value = $this->connection->query("SELECT title FROM {photos_image} WHERE fid = :fid", [':fid' => $fid])->fetchField();
            $message = $value;
            break;

          case 'des':
            $value = $this->connection->query("SELECT des FROM {photos_image} WHERE fid = :fid", [':fid' => $fid])->fetchField();
            $message = $value;
            break;
        }
        // Clear cache.
        $pid = $this->connection->query("SELECT pid FROM {photos_image} WHERE fid = :fid", [':fid' => $fid])->fetchField();
        if ($pid) {
          Cache::invalidateTags(['node:' . $pid, 'photos:album:' . $pid]);
        }
        Cache::invalidateTags(['photos:image:' . $fid]);
      }
    }

    // Build plain text response.
    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');
    $response->setContent($message);
    return $response;
  }

  /**
   * Set album cover.
   */
  public function setAlbumCover($node, $file) {
    $nid = $node->id();
    $pid = $this->connection->query('SELECT pid FROM {photos_image} WHERE fid = :fid', [':fid' => $file])->fetchField();
    if ($pid == $nid) {
      $album = new PhotosAlbum($pid);
      $album->setCover($file);
      $get_destination = $this->requestStack->getCurrentRequest()->query->get('destination');
      $goto = $get_destination ?: 'photos/album/' . $nid;
      $goto = Url::fromUri('base:' . $goto)->toString();
      return new RedirectResponse($goto);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
