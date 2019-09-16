<?php

namespace Drupal\atwork_extra_fields\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Drupal\Core\Url;

/**
* Example Extra field with formatted output.
*
* @ExtraFieldDisplay(
*   id = "node_share",
*   label = @Translation("Share"),
*   bundles = {
*     "node.*",
*   }
* )
*/
class atworkShare extends ExtraFieldDisplayFormattedBase {

 use StringTranslationTrait;

 /**
  * {@inheritdoc}
  */
 public function viewElements(ContentEntityInterface $entity) {

   $share = '';
   $share .= '<span class="share-link-holder">';
   $share .= '<input type="button" data-toggle="modal" data-target="#dialog-share" value="Share" class="social-bar-share-button" />';
   $share .= '</span>';
   $elements = ['#markup' => $share,
                '#allowed_tags' => ['input'],
               ];
   return $elements;
 }

}
