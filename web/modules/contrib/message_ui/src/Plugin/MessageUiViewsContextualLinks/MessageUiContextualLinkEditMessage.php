<?php

namespace Drupal\message_ui\Plugin\MessageUiViewsContextualLinks;

use Drupal\Core\Url;
use Drupal\message_ui\MessageUiViewsContextualLinksBase;
use Drupal\message_ui\MessageUiViewsContextualLinksInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Contextual link to edit the message.
 *
 * @MessageUiViewsContextualLinks(
 *  id = "edit",
 *  label = @Translation("Button the delete a message."),
 *  weight = 1
 * )
 */
class MessageUiContextualLinkEditMessage extends MessageUiViewsContextualLinksBase implements MessageUiViewsContextualLinksInterface, ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return $this->message->access('edit');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouterInfo() {
    return [
      'title' => t('Edit'),
      'url' => Url::fromRoute('entity.message.edit_form', ['message' => $this->message->id()]),
    ];
  }

}
