<?php

namespace Drupal\photos_access\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default controller for the photos_access module.
 */
class DefaultController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The renderer service.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilderInterface $form_builder, RendererInterface $renderer) {
    $this->formBuilder = $form_builder;
    $this->renderer = $renderer;
  }

  /**
   * Photos album password required page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The album node that requires the password.
   */
  public function photosAccessPasswordPage(NodeInterface $node) {
    if ($node) {
      $pass_form = $this->formBuilder->getForm('\Drupal\photos_access\Form\PhotosAccessPasswordForm', $node);
      $output = $this->renderer->render($pass_form);
      return ['#markup' => $output];
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
