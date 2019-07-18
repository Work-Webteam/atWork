<?php

namespace Drupal\tether_stats;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines a class to build a listing of derivative entities.
 *
 * @see \Drupal\tether_stats\Entity\TetherStatsDerivative
 */
class TetherStatsDerivativeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['name'] = $this->t('Derivative name');
    $header['derivative_entity_type'] = $this->t('Entity Type Constraint');
    $header['derivative_bundle'] = $this->t('Bundle Constraint');
    $header['usage'] = $this->t('Elements Created');
    $header['description'] = $this->t('Description');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $row['name'] = $entity->id();
    $row['derivative_entity_type'] = $entity->getDerivativeEntityType();
    $row['derivative_bundle'] = $entity->getDerivativeBundle();
    $row['usage'] = $entity->getUsageCount();
    $row['description'] = SafeMarkup::checkPlain($entity->getDescription());
    $row['status'] = $entity->status() ? 'Enabled' : 'Disabled';
    return $row + parent::buildRow($entity);
  }

}
