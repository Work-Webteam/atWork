<?php

namespace Drupal\likeit\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\likeit\Access\LikeItCsrfTokenGenerator;
use Drupal\likeit\Event\LikeItEvent;
use Drupal\likeit\Event\LikeItEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LikeItController.
 */
class LikeItController extends ControllerBase {

  /**
   * Custom token generator service.
   *
   * @var \Drupal\likeit\Access\LikeItCsrfTokenGenerator
   */
  protected $csrfGenerator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * LikeItController constructor.
   *
   * @param \Drupal\likeit\Access\LikeItCsrfTokenGenerator $token_generator
   *   The the custom token generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(LikeItCsrfTokenGenerator $token_generator, RequestStack $request_stack, EventDispatcherInterface $event_dispatcher) {
    $this->csrfGenerator = $token_generator;
    $this->requestStack = $request_stack;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('likeit_csrf_token'),
      $container->get('request_stack'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Like action.
   *
   * @param string $entity
   *   Target entity name.
   * @param string $id
   *   Target entity id.
   * @param string $html_id
   *   Link DOM id.
   * @param string $token
   *   Csrf token.
   *
   * @return string
   *   Return response string.
   */
  public function like($entity, $id, $html_id, $token = '') {
    $session_id = NULL;
    $user = $this->currentUser();
    $entity_arr = explode(':', $entity);
    $entity_type = $entity_arr[0];
    $object = $this->entityTypeManager()->getStorage($entity_type)->load($id);
    if ($object) {
      try {
        // Check CSRF token.
        if ($this->csrfGenerator->validate($token, $html_id)) {
          $session_id = likeit_do_like($object, $user);

          // Crete new like/unlike event.
          $event = new LikeItEvent($object);

          // Use the event dispatcher service to notify any event subscribers.
          $this->eventDispatcher->dispatch(LikeItEvents::LIKE, $event);
        }
      }
      catch (\LogicException $e) {
        // Do nothing on fail.
      }
    }

    return $this->response($entity, $id, $session_id, $html_id);
  }

  /**
   * Unlike action.
   *
   * @param string $entity
   *   Target entity name.
   * @param string $id
   *   Target entity id.
   * @param string $html_id
   *   Link DOM id.
   * @param string $token
   *   Csrf token.
   *
   * @return string
   *   Return response string.
   */
  public function unlike($entity, $id, $html_id, $token = '') {
    $session_id = '';
    $user = $this->currentUser();
    $entity_arr = explode(':', $entity);
    $entity_type = $entity_arr[0];
    $object = $this->entityTypeManager()->getStorage($entity_type)->load($id);
    if ($object) {
      try {
        // Check CSRF token.
        if ($this->csrfGenerator->validate($token, $html_id)) {
          $session_id = likeit_do_unlike($object, $user);

          // Crete new like/unlike event.
          $event = new LikeItEvent($object);

          // Use the event dispatcher service to notify any event subscribers.
          $this->eventDispatcher->dispatch(LikeItEvents::UNLIKE, $event);
        }
      }
      catch (\LogicException $e) {
        // Do nothing on fail.
      }
    }

    return $this->response($entity, $id, $session_id, $html_id);
  }

  /**
   * Provides response to the user.
   *
   * @param string $target
   *   Target entity.
   * @param string $id
   *   Target entity id.
   * @param string $session_id
   *   User session id.
   * @param string $html_id
   *   Element DOM id.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response to the user.
   */
  public function response($target, $id, $session_id, $html_id) {
    $response = new AjaxResponse();

    $account = $this->currentUser();
    if ($account->isAnonymous() && !likeit_get_cookie()) {
      likeit_set_cookie($session_id);
    }

    $element = likeit_get_link($target, $id);
    $link_id = '#' . $html_id;

    if (empty($element['#content']['link'])) {
      if (!empty($element['#content']['view'])) {
        $content = $element['#content']['view'];
      }
      else {
        $content = $element['#content']['message'];
      }
    }
    else {
      $new_html_id = $element['#content']['link']['#attributes']['id'];
      $token = $this->csrfGenerator->get($new_html_id);
      $element['#content']['link']['#url']->setRouteParameter('token', $token);
      $content = $element['#content']['link'];
    }

    // Update like/unlike link.
    $replace = new ReplaceCommand($link_id, render($content));
    $response->addCommand($replace);

    return $response;
  }

  /**
   * Check user permissions to like/unlike/view.
   *
   * @param string $action
   *   Action name.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account or null.
   *
   * @return bool
   *   Access grant status.
   */
  public static function checkAccess($action = 'like', AccountInterface $account = NULL) {
    if (!$account) {
      $account = static::currentUser();
    }

    switch ($action) {
      case 'like':
        return $account->hasPermission('likeit_like');

      case 'unlike':
        return $account->hasPermission('likeit_unlike');

      default:
        return $account->hasPermission('likeit_view');
    }
  }

}
