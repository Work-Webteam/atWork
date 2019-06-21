<?php

namespace Drupal\message_ui;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class MessageUIFieldDisplayManagerService.
 *
 * @package Drupal\message_ui
 */
class MessageUIFieldDisplayManagerService implements MessageUIFieldDisplayManagerServiceInterface {

  /**
   * The entity storage manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldsDisplay($template) {
    $this->entityTypeManager->getStorage('entity_form_display')
      ->resetCache();

    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $form_display */
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load("message.{$template}.default");

    if (!$form_display) {
      $form_display = \Drupal::entityTypeManager()
        ->getStorage('entity_form_display')
        ->create([
          'targetEntityType' => 'message',
          'bundle' => $template,
          'mode' => 'default',
          'status' => TRUE,
        ]);

      foreach (array_keys($form_display->get('hidden')) as $hidden) {
        $form_display->setComponent($hidden, ['field_name' => $hidden]);
        $form_display->save();
      }
    }
  }

}
