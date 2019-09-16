<?php

namespace Drupal\atwork_extra_fields\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Example Extra field Display.
 *
 * @ExtraFieldDisplay(
 *   id = "article_only",
 *   label = @Translation("Author with Create Date"),
 *   bundles = {
 *     "node.article",
 *     "node.page"
 *   }
 * )
 */
class atworkArticleAuthor extends ExtraFieldDisplayFormattedBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $date = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'atwork_datetime');
    $time = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'atwork_time');
    $id = $entity->field_author_information->target_id;
    $author = $entity->field_author_information->entity->field_user_display_name->value;
    $url = Url::fromUserInput("/user/".$id);
    $link = \Drupal::l($author, $url);
    $elements = ['#markup' => $link . ' posted on ' . $date . ' ' . $time];

    return $elements;
  }

}
