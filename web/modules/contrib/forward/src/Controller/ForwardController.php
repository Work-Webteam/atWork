<?php

namespace Drupal\forward\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\forward\ForwardFormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for the Forward module.
 */
class ForwardController extends ControllerBase {

  /**
   * The forward form builder service.
   *
   * @var \Drupal\forward\Form\ForwardFormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a ForwardController object.
   *
   * @param \Drupal\forward\Form\ForwardFormBuilder $form_builder
   *   The forward form builder service.
   */
  public function __construct(ForwardFormBuilder $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('forward.form_builder')
    );
  }

  /**
   * Build a Forward form when accessed as a separate page through the router.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being forwarded.
   *
   * @return array
   *   The render array for the form.
   */
  public function buildForm(EntityInterface $entity) {
    // Force the "link" interface so the Forward form page doesn't build inside a fieldset.
    $settings = $this->config('forward.settings')->get();
    $settings['forward_interface_type'] = 'link';
    return $this->formBuilder->buildForwardEntityForm($entity, $settings);
  }

}
