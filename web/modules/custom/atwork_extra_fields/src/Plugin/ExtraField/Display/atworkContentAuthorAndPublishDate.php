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
*   id = "author_publish_date",
*   label = @Translation("Author with Publish Date"),
*   bundles = {
*     "node.*",
*   }
* )
*/
class atworkContentAuthorAndPublishDate extends ExtraFieldDisplayFormattedBase {

 /**
  * {@inheritdoc}
  */
 public function viewElements(ContentEntityInterface $entity) {

   $date = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'atwork_datetime');
   $time = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'atwork_time');

   // if node is type article then check for author_information field info
   if ($entity->bundle() == "article") {
     // if not an article use entity owner for author name
     if (!empty($entity->get('field_author_information')->getValue())) {
       $id = $entity->field_author_information->target_id;
       $author = $entity->field_author_information->entity->field_user_display_name->value;
     }
     // otherwise default to Employee News user (id:1)
     else {
       // TODO: make this configurable?
       $id = 1;
       $user = \Drupal\user\Entity\User::load($id);
       $author = $user->field_user_display_name->value;
     }
   }
   // not an article so use entity owner for user name
   else {
     $id = $entity->getOwnerId();
     $user = \Drupal\user\Entity\User::load($id);
     $author = $user->field_user_display_name->value;
   }
   $url = Url::fromUserInput("/user/".$id);
   $link = \Drupal::l($author, $url);
   $elements = ['#markup' => $link . ' posted on ' . $date . ' ' . $time];

   return $elements;
 }

}

