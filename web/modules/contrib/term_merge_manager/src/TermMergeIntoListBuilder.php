<?php

namespace Drupal\term_merge_manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Term merge into entities.
 *
 * @ingroup term_merge_manager
 */
class TermMergeIntoListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Term merge into ID');
    $header['vid'] = $this->t('Vocabulary');
    $header['tid'] = $this->t('Term');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\term_merge_manager\Entity\TermMergeInto */
    $row['id'] = $entity->id();
    $row['vid'] = $entity->getVid();
    $row['tid'] = Link::createFromRoute(
      $entity->getTid().' ('.$entity->getName().')',
      'entity.taxonomy_term.edit_form',
      ['taxonomy_term' => $entity->getTid()]
    );
    return $row + parent::buildRow($entity);
  }

}
