<?php

namespace Drupal\atwork_mail_send_update\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;

/**
 * Save queue item in a node.
 *
 * To process the queue items whenever Cron is run,
 * we need a QueueWorker plugin with an annotation which defines
 * to which queue it applied.
 *
 * @QueueWorker(
 *   id = "SubQueue",
 *   title = @Translation("Clean up subscriptions for old users"),
 *   cron = {"time" = 10}
 * )
 */
class SubQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
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
   */
  public function processItem($item) {
    // Take each uid and turn off the email option.
    try {
      // Load user, turn off subscription, save user.
      $user_sub = User::load($item);
      if ($user_sub) {
        $user_sub->set('message_subscribe_email', 0);
        // Check if user is valid.
        $violations = $user_sub->validate();
        if (count($violations) === 0) {
          // If they are valid, then save the user.
          $user_sub->save();
          // Log in the watchdog for debugging purpose.
          $this->loggerChannelFactory->get('debug')
            ->debug('Blocked user @user subscriptions have been turned off',
              [
                '@user' => $user_sub->get('name'),
              ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('Warning')
        ->warning('Exception for subscription queue @error',
          ['@error' => $e->getMessage()]);
    }
  }

}
