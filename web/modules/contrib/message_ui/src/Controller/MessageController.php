<?php

namespace Drupal\message_ui\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\message\Entity\Message;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\message\MessageTemplateInterface;

/**
 * Controller for adding messages.
 */
class MessageController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The access handler object.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  private $accessHandler;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a MessageUiController object.
   */
  public function __construct() {
    $this->entityManager = \Drupal::entityManager();
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('message');
  }

  /**
   * Generates output of all message template with permission to create.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the message templates that can be added;
   *   however, if there is only one message template defined for the site, the
   *   function will return a RedirectResponse to the message.add page for that
   *   one message template.
   */
  public function addPage() {
    $content = [];

    // Only use message templates the user has access to.
    foreach ($this->entityManager()->getStorage('message_template')->loadMultiple() as $template) {
      $access = $this->entityManager()
        ->getAccessControlHandler('message')
        ->createAccess($template->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$template->id()] = $template;
      }
    }

    // Bypass the message/add listing if only one message template is available.
    if (count($content) == 1) {
      $template = array_shift($content);
      return $this->redirect('message_ui.add', ['message_template' => $template->id()]);
    }

    // Return build array.
    if (!empty($content)) {
      return ['#theme' => 'message_add_list', '#content' => $content];
    }
    else {
      $url = Url::fromRoute('message.template_add');
      return ['#markup' => 'There are no messages templates. You can create a new message template <a href="/' . $url->getInternalPath() . '">here</a>.'];
    }
  }

  /**
   * Generates form output for adding a new message entity of message_template.
   *
   * @param \Drupal\message\MessageTemplateInterface $message_template
   *   The message template object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function add(MessageTemplateInterface $message_template) {
    $message = Message::create(['template' => $message_template->id()]);
    $form = $this->entityFormBuilder()->getForm($message);

    return $form;
  }

  /**
   * Generates form output for deleting of multiple message entities.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function deleteMultiple() {
    // @todo - create the path corresponding to below.
    // From devel module - admin/config/development/message_delete_multiple.
    // @todo pass messages to be deleted in args?
    $build = \Drupal::formBuilder()->getForm('Drupal\message_ui\Form\DeleteMultiple');

    return $build;
  }

}
