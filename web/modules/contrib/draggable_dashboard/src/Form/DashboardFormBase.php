<?php

namespace Drupal\draggable_dashboard\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for draggable dashboard add/edit forms.
 */
abstract class DashboardFormBase extends FormBase {

  /**
   *  Maximum dashboard columns count
   */
  const MAX_COLUMNS_COUNT = 4;

  /**
   * An array containing the dashboard ID, etc.
   *
   * @var \Drupal\draggable_dashboard\Entity\DashboardEntity
   */
  protected $dashboard;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   *
   * DashboardFormBase constructor.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   */
  public function __construct(ThemeManagerInterface $theme_manager, BlockManagerInterface $block_manager) {
    $this->themeManager = $theme_manager;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme.manager'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Builds the path used by the form.
   *
   * @param int|null $did
   *   Either the unique path ID, or NULL if a new one is being created.
   */
  abstract protected function buildDashboard($did);

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $did = NULL) {
    $this->dashboard = $this->buildDashboard($did);

    $form['title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 255,
      '#default_value' => $this->dashboard->get('title'),
      '#description' => '',
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $this->dashboard->get('description'),
      '#description' => '',
    ];

    $form['columns'] = [
      '#type' => 'select',
      '#title' => $this->t('Dashboard columns'),
      '#options' => [
        1 => $this->t('1 Column'),
        2 => $this->t('2 Columns'),
        3 => $this->t('3 Columns'),
        4 => $this->t('4 Columns')
      ],
      '#default_value' => $this->dashboard->get('columns'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove unnecessary values.
    $form_state->cleanValues();

    $did = $form_state->getValue('did', 0);

    $args = [
      'id' => empty($did) ? time() : $did,
      'title' => $form_state->getValue('title', ''),
      'description' => $form_state->getValue('description', ''),
      'columns' => $form_state->getValue('columns', 2),
    ];

    foreach ($args as $key => $value){
      $this->dashboard->set($key, $value);
    }

    $this->dashboard->save();

    // delete block from column if number of columns has been changed
    if (!empty($did) && $this->dashboard->get('columns') > $args['columns']){
      $all_blocks = json_decode($this->dashboard->get('blocks'), TRUE);
      for ($i = $args['columns'] + 1; $i <= self::MAX_COLUMNS_COUNT; $i++){
        foreach ($all_blocks as $key => $relation){
          if ($relation['cln'] == $i){
            $block = Block::load($relation['bid']);
            if ($block){
              $block->delete();
            }
            unset($all_blocks[$key]);
          }
        }
      }
      $this->dashboard->set('blocks', json_encode($all_blocks))->save();
    }

    // invalidate block list cache
    $this->blockManager->clearCachedDefinitions();

    drupal_set_message($this->t('Dashboard has been saved.'));

    // redirect just created dashboard to manage blocks page
    if (empty($did)){
      $form_state->setRedirect('draggable_dashboard.manage_dashboard', ['did' => $args['id']]);
    }
    // redirect to overview dashboards in success edit action
    else{
      $form_state->setRedirect('draggable_dashboard.overview');
    }
  }

}
