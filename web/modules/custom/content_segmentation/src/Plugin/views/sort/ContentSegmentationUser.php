<?php

namespace Drupal\content_segmentation\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Basic sort handler.
 *
 * @ViewsSort("User segmentation")
 */
class ContentSegmentationUser extends SortPluginBase {

  /**
   * Called to add the sort to a query.
   */
  public function query() {
  // kint($this->query->addOrderBy);
    //$this->ensureMyTable();
    //$percentage = "round(( $this->tableAlias.$this->realField/node__field_funding_goal.field_funding_goal_value * 100 ),2)";
    $this->query->addOrderBy(NULL,10, 'DESC');
  }

}