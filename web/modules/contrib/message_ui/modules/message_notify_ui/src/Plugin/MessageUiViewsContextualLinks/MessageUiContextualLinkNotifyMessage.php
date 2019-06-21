<?php

namespace Drupal\message_notify_ui\Plugin\MessageUiViewsContextualLinks;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\message_ui\MessageUiViewsContextualLinksBase;
use Drupal\message_ui\MessageUiViewsContextualLinksInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Notify message contextual plugin link.
 *
 * @MessageUiViewsContextualLinks(
 *  id = "notify",
 *  label = @Translation("Notify."),
 *  weight = 4
 * )
 */
class MessageUiContextualLinkNotifyMessage extends MessageUiViewsContextualLinksBase implements MessageUiViewsContextualLinksInterface, ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return $this->currentUser->hasPermission('send message through the ui');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouterInfo() {
    return [
      'title' => t('Notify'),
      'url' => Url::fromRoute('entity.message.notify_form', ['message' => $this->message->id()]),
    ];
  }

}
