<?php
namespace Drupal\content_segmentation\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\paragraphs\Entity\Paragraph;
/**
 * ContentSegmentationController.
 */
class ContentSegmentationController extends ControllerBase {
  /**
   * {@inheritdoc}
   */
  public function delete(Paragraph $paragraph, Request $request) {
    if ($paragraph) $paragraph->delete();
    //$response = new RedirectResponse($request->headers->get('referer'));
    //return $response->send();
    $previousUrl = \Drupal::request()->server->get('HTTP_REFERER');
    $fake_request = Request::create($previousUrl);
    $url_object = \Drupal::service('path.validator')->getUrlIfValid($fake_request->getRequestUri());
    if ($url_object) {
      $route_name = $url_object->getRouteName();
      return $this->redirect($route_name);
    }
  }
}