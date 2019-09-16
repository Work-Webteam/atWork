<?php

namespace Drupal\atwork_extra_fields\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;

/**
* Example Extra field with formatted output.
*
* @ExtraFieldDisplay(
*   id = "article_related_content",
*   label = @Translation("Article Related Content"),
*   bundles = {
*     "node.article",
*   }
* )
*/
class atworkArticleRelatedContent extends ExtraFieldDisplayFormattedBase {

 use StringTranslationTrait;

 /**
  * {@inheritdoc}
  */
 public function viewElements(ContentEntityInterface $entity) {

   // Load Related Content Block
   $block = \Drupal\block\Entity\Block::load('views_block__related_content_block_1');
   $block_content = \Drupal::entityTypeManager()
     ->getViewBuilder('block')
     ->view($block);
// ksm($block_content);

$content = [
 'view' => [
   '#type' => 'view',
   '#name' => 'related_content',
   '#display_id' => 'block_1',
   '#embed' => TRUE,
 ],
];

   $elements = [$content];

   return $elements;
 }

}
