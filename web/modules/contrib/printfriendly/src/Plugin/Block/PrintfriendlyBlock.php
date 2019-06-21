<?php

/**
 * @file
 */

namespace Drupal\printfriendly\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Creates a 'Printfriendly' Block
 * @Block(
 * id = "block_printfriendly",
 * admin_label = @Translation("printfriendly"),
 * )
 */
 
class PrintfriendlyBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
	  return printfriendly_create_button();
    }
}
