<?php

namespace Drupal\insert\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\ImageEffectBase;

/**
 * The effect does not alter images in any way. Its purpose is to just identify
 * images that shall be wrapped in links to their corresponding original sized
 * images when inserting images using the Insert button.
 *
 * @ImageEffect(
 *   id = "insert_image_linked",
 *   label = @Translation("Link (Insert)"),
 *   description = @Translation("Link the image to the original sized image when inserting it using the Insert button.")
 * )
 */
class InsertLinkedEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    return TRUE;
  }
}
