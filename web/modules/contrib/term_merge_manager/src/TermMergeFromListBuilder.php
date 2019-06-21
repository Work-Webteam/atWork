<?php

namespace Drupal\term_merge_manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Term merge from entities.
 *
 * @ingroup term_merge_manager
 */
class TermMergeFromListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Term merge from ID');
    $header['vid'] = $this->t('Vocabulary');
    $header['name'] = $this->t('Name');
    $header['into'] = $this->t('Into');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\term_merge_manager\Entity\TermMergeFrom */
    $row['id'] = $entity->id();
    $row['vid'] = $entity->getVid();
    $row['name'] = $entity->label();
    $row['into'] = Link::createFromRoute(
      $entity->getIntoId() . ' ('.$entity->getIntoName().')',
      'entity.taxonomy_term.edit_form',
      ['taxonomy_term' => $entity->getIntoId()]
    );
    return $row + parent::buildRow($entity);
  }

}
