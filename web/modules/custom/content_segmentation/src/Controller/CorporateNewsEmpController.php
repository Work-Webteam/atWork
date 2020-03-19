<?php
namespace Drupal\content_segmentation\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
/**
 * Class DefaultController.
 */
class CorporateNewsEmpController extends ControllerBase {
  /**
   * Content.
   *
   * @return string
   *   Return Content string.
   */
  public function content() {
    $elements = [];
    
    $views_name = 'content_emp';
    $display_id = 'page_1';
    $view = Views::getView($views_name);
    $view->setDisplay($display_id);
    $elements['view'] = $view->render($display_id);
    $elements['view']['#weight'] = 0;

    
    $elements['form_search'] = \Drupal::formBuilder()->getForm('\Drupal\content_segmentation\Form\CorporateNewsEmp');
    $elements['form_search']['#weight'] = 1;

    return $elements;
  }
}