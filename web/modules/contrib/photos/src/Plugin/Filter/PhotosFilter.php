<?php

namespace Drupal\photos\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to embed image and albums from the photos module.
 *
 * @Filter(
 *   id = "photos_filter",
 *   title = @Translation("Insert images and albums from the photos module"),
 *   description = @Translation("Example: [photos=image]id=55,54,53,52|align=right[/photos] or [photos=album]id=134[/photos] or [photos=album]id=134|limit=6[/photos]."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class PhotosFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $text = ' ' . $text . ' ';
    $text = preg_replace_callback('/\[photos=(.*?)\](.*?)\[\/photos\]/ms', '_photos_filter_process', $text);
    $text = mb_substr($text, 1, -1);
    $result->setProcessedText($text);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    switch ($long) {
      case 0:
        return t('Insert an image: [photos=image]id=55[/photos], insert multiple images: [photos=image]id=55,56,57,58[/photos], insert album: [photos=album]id=10[/photos].');

      case 1:
        $tip = '<h3>Insert images and albums</h3>';
        $item[] = t('Insert an image: [photos=image]id=55[/photos].');
        $item[] = t('Insert multiple images: [photos=image]id=55,56,57,58,59[/photos].');
        $item[] = t('Optional attributes: align, e.g: [photos=image]id=55|align=left[/photos] or [photos=image]id=55,56,57|align=right[/photos].');
        $t1 = [
          '#theme' => 'item_list',
          '#items' => $item,
          '#title' => t('Images'),
        ];
        $tip .= \Drupal::service('renderer')->render($t1);

        $item = [];
        $item[] = t('Insert album: [photos=album]id=10[/photos]. The default will display the album cover. You can display additional images with the "limit" property.');
        $item[] = t('Optional attributes: align or limit, e.g: [photos=album]id=10|align=left[/photos] or [photos=album]id=10|align=right|limit=5[/photos].');
        $t2 = [
          '#theme' => 'item_list',
          '#items' => $item,
          '#title' => t('Albums'),
        ];
        $tip .= \Drupal::service('renderer')->render($t2);

        $tip .= t('This is similar to bbcode.');
        return $tip;
    }
  }

}
