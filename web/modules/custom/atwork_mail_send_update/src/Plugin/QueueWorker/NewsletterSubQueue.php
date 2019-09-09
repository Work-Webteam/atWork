<?php

namespace Drupal\atwork_mail_send_update\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Save queue item in a node.
 *
 * To process the queue items whenever Cron is run,
 * we need a QueueWorker plugin with an annotation which defines
 * to which queue it applied.
 *
 * @QueueWorker(
 *   id = "NewsletterSubQueue",
 *   title = @Translation("Clean up newsletter subscriptions for old users"),
 *   cron = {"time" = 10}
 * )
 */
class NewsletterSubQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
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
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   *   $item here will be an entity id for the subscription.
   *   We can then turn off subscriptions for each entity id we have.
   */
  public function processItem($item) {
    // Take each uid and turn off the email option.
    try {
      // Update subscription status by entity id.
      $connection = \Drupal::database();
      $query = $connection->update('simplenews_subscriber__subscriptions')
        ->fields([
          'subscription_status' => 0,
        ])
        ->condition('entity_id', $item, '=')
        ->execute();
      if ($query) {
        // Logging to aid in debugging.
        $this->loggerChannelFactory->get('debug')
          ->debug('Subscription for entity @item has been updated, this subscription will no longer be sent.',
            [
              '@item' => $item,
            ]);
      }
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('Warning')
        ->warning('Exception for newsletter queue @error',
          ['@error' => $e->getMessage()]);
    }
  }

}
