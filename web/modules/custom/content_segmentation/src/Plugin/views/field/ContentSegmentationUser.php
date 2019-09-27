<?php
 
/**
 * @file
 * Definition of Drupal\content_segmentation\Plugin\views\field\ContentSegmentationUser
 */
 
namespace Drupal\content_segmentation\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
 
/**
 * Field handler to flag the current user content segmentation.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("content_segmentation_user")
 */
class ContentSegmentationUser extends FieldPluginBase {
 
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }
 
  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }
 
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    //$types = NodeType::loadMultiple();
    //$options = [];
    //foreach ($types as $key => $type) {
    //  $options[$key] = $type->label();
    //}
    //$form['node_type'] = array(
    //  '#title' => $this->t('Which node type should be flagged?'),
    //  '#type' => 'select',
    //  '#default_value' => $this->options['node_type'],
    //  '#options' => $options,
    //);
 
    parent::buildOptionsForm($form, $form_state);
  }
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $user = \Drupal::currentUser()->id();

    $node = $values->_entity;
    $nid = $node->id();

    $connection = \Drupal::database();

    $connection = \Drupal::database();
    $query = $connection->query("SELECT weight FROM `draggableviews_structure` 
                                 WHERE entity_id = (SELECT entity_id 
                                 FROM `paragraph__field_corporate_news` 
                                 WHERE field_corporate_news_target_id = :id )", 
                                [':id' => $nid]);
    //$result = $query->fetchAll();
    $result = $query->fetchAssoc();
    $weight = $result['weight'];
    if(is_null($weight)){
      return 0;
    }
    
    $query = $connection->query("SELECT entity_id FROM `user__field_emp`
                                 WHERE field_emp_target_id = (SELECT emp.field_emp_target_id 
                                                              FROM `paragraph__field_corporate_news` as cn
                                                              INNER JOIN `paragraph__field_emp` as emp 
                                                              ON (cn.entity_id = emp.entity_id and cn.bundle = emp.bundle)
                                                              WHERE cn.field_corporate_news_target_id = :id )", 
                                                              [':id' => $nid]);
    $result = $query->fetchAssoc();
    if($result['entity_id'] != $user){
      return 0;
    }
    return $weight;
  }
}