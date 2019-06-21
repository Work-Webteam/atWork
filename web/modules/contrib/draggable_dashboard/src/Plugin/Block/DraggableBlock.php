<?php

namespace Drupal\draggable_dashboard\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\draggable_dashboard\Entity\DashboardEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a draggable block with a simple text.
 *
 * @Block(
 *   id = "draggable_dashboard_block",
 *   admin_label = @Translation("Draggable Block"),
 *   deriver = "Drupal\draggable_dashboard\Plugin\Block\DraggableBlockDeriver"
 * )
 */
class DraggableBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * @var array
   */
  protected $dashboard;

  /**
   * @var string
   */
  protected $pageTitle;

  /**
   * DraggableBlock constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThemeManagerInterface $theme_manager, TitleResolverInterface $title_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->themeManager = $theme_manager;
    $this->titleResolver = $title_resolver;

    $config = $this->getConfiguration();
    $did = preg_replace('%[^\d]%', '', $config['id']);
    $this->dashboard = DashboardEntity::load($did);
    $this->pageTitle = $this->titleResolver->getTitle(\Drupal::request(), \Drupal::routeMatch()->getRouteObject());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme.manager'),
      $container->get('title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    if (!empty($this->dashboard)){
      $columns = [];
      $all_blocks = json_decode($this->dashboard->get('blocks'), TRUE);
      $max_blocks = 0;
      // create dashboard columns
      for ($i = 1; $i <= $this->dashboard->get('columns'); $i++){
        $blocks = [];
        if (!empty($all_blocks)){
          foreach ($all_blocks as $key => $relation){
            if ($relation['cln'] == $i){
              $blocks[] = $relation;
            }
          }
        }
        if (!empty($blocks)){
          if ($max_blocks < count($blocks)){
            $max_blocks = count($blocks);
          }
          foreach ($blocks as $relation){
            $block = Block::load($relation['bid']);
            if (empty($block)) {
              continue;
            }
            $block_manager = \Drupal::service('plugin.manager.block');
            // You can hard code configuration or you load from settings.
            $config = $block->getPlugin()->getConfiguration();
            $isTitleVisible = empty($config['label_display']) ? FALSE : TRUE;
            $config['label_display'] = 0;

            $plugin_block = $block_manager->createInstance($block->getPluginId(), $config);

            if ($plugin_block instanceof MainContentBlockPluginInterface) {
              // $plugin_block->setMainContent($this->mainContent);
            }
            elseif ($plugin_block instanceof TitleBlockPluginInterface) {
               $plugin_block->setTitle($this->pageTitle);
            }

            // Some blocks might implement access check.
            // Return empty render array if user doesn't have access.
            // $access_result can be boolean or an AccessResult class
            if ($plugin_block->access(\Drupal::currentUser())) {

              $render = \Drupal::entityTypeManager()
                ->getViewBuilder('block')
                ->view($block);

              if (!isset($render['#lazy_builder'])){
                unset($render['#pre_render']);
                $content = $plugin_block->build();
                $render['content'] = $content;
              }
              else{
                unset($render['#lazy_builder']);
                $content = $plugin_block->build();
                $render['content'] = $content;
              }
              $columns[$i][] = [
                'id' => $relation['bid'],
                'title' => $isTitleVisible ? $config['label'] : '',
                'view' => [
                  'data' => $render
                ]
              ];
            }
          }
        }
      }
    }

    $build = [
      '#theme' => 'draggable_dashboard_block',
      '#dashboard' => $this->dashboard,
      '#columns' => $columns,
      '#cache' => [
        'max-age' => 0
      ],
      '#attached' => [
        'library' => [
          'draggable_dashboard/draggable_dashboard.frontend'
        ]
      ]
    ];

    $account = \Drupal::currentUser()->getAccount();
    if ($account->hasPermission('administer_draggable_dashboard')){
      $build['#attached']['library'][] = 'draggable_dashboard/draggable_dashboard.draggable';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }
}