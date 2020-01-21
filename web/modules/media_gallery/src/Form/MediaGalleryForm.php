<?php

namespace Drupal\media_gallery\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the media gallery entity edit forms.
 */
class MediaGalleryForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New media gallery %label has been created.', $message_arguments));
      $this->logger('media_gallery')->notice('Created new media gallery %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The media gallery %label has been updated.', $message_arguments));
      $this->logger('media_gallery')->notice('Updated new media gallery %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.media_gallery.canonical', ['media_gallery' => $entity->id()]);
  }

}
