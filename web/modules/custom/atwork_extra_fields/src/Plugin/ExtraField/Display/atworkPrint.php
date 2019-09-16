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
*   id = "node_print",
*   label = @Translation("Print"),
*   bundles = {
*     "node.*",
*   }
* )
*/
class atworkPrint extends ExtraFieldDisplayFormattedBase {

 use StringTranslationTrait;

 /**
  * {@inheritdoc}
  */
 public function viewElements(ContentEntityInterface $entity) {

   $print = "";
   // $print .= '<div ">';
   // $print .= '<div onclick="window.print()">Print</div>';
   // $print .= '</div>';
   // $elements = ['#markup' => $print];
   $elements = ['#type' => 'button',
                '#title' => 'Print',
                '#value' => 'Print',
                '#attributes' => ['onclick' => 'window.print();', 'class' => ['social-bar-print-button']],
                '#prefix' => '<div id="printBtn" class="printer-button">',
                '#suffix' => '<div>',
              ];

   return $elements;
 }

}
