<?php

namespace Drupal\rate;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to return permissions based on entity type for rate module.
 *
 * @package Drupal\rate
 */
class RatePermissions implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The config factory wrapper to fetch settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs Permissions object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('rate.settings');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get permissions for Rate module.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    $enabled_types_widgets = $this->config->get('enabled_types_widgets');
    if (!empty($enabled_types_widgets)) {
      foreach ($enabled_types_widgets as $entity_type_id => $entity_types) {
        foreach ($entity_types as $bundle => $settings) {
          if ($bundle == $entity_type_id) {
            $perm_index = 'cast rate vote on ' . $entity_type_id . ' of ' . $bundle;
            $permissions[$perm_index] = [
              'title' => $this->t('Can vote on :type', [':type' => $entity_type_id]),
            ];
          }
          else {
            $perm_index = 'cast rate vote on ' . $entity_type_id . ' of ' . $bundle;
            $permissions[$perm_index] = [
              'title' => $this->t('Can vote on :type type of :bundle', [':bundle' => $bundle, ':type' => $entity_type_id]),
            ];
          }
        }
      }
    }
    return $permissions;
  }

}
