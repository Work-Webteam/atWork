<?php

namespace Drupal\content_segmentation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class CorporateNewsEmp.
 */
class UserLinks extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_links';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#parents'] = [];
    $entityManager = \Drupal::service('entity.manager');
    //$bundles = $entityManager->getBundleInfo('paragraph');

    $entity = $entityManager->getStorage('paragraph')->create([ 'type' => 'user_shortcut' ]);
    $form_state->set('entity', $entity);

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $entityManager->getStorage('entity_form_display')->load('paragraph.user_shortcut.default');
    $form_state->set('form_display', $form_display);

    foreach ($form_display->getComponents() as $name => $component) {
      $widget = $form_display->getRenderer($name);
      if (!$widget) {
        continue;
      }    
      $items = $entity->get($name);
      $items->filterEmptyItems();
      $form[$name] = $widget->form($items, $form, $form_state);
      $form[$name]['#access'] = $items->access('edit');
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Item'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) { 
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $form_state->get('form_display');
    $entity = $form_state->get('entity');
    $entity->field_us_user->target_id = \Drupal::currentUser()->id();
    $extracted = $form_display->extractFormValues($entity, $form, $form_state);
    $extracted['field_us_user'] = 'field_us_user';
    $entity->save();
  }

}
