<?php

/**
 * @file
 * Contains Drupal\user_alert\UserAlertListBuilder.
 */

namespace Drupal\user_alert;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of User alert entities.
 *
 * @ingroup user_alert
 */
class UserAlertListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * Constructs a new UserAlertListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['created'] = $this->t('Created');
    $header['updated'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\user_alert\Entity\UserAlert */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.user_alert.edit_form', array(
          'user_alert' => $entity->id(),
        )
      )
    );
    $row['type'] = $entity->bundle();
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');
    $row['updated'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No results.');
    return $build;
  }
}
