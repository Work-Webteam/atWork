<?php

namespace Drupal\draggable_dashboard\Form;

use Drupal\block\Entity\Block;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\draggable_dashboard\Entity\DashboardEntity;
use Drupal\draggable_dashboard\Entity\DashboardEntityInterface;

/**
 * Provides the draggable dashboard edit form.
 */
class DashboardManageForm extends DashboardFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'draggable_dashboard_manage';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDashboard($did) {
    return DashboardEntity::load($did);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $did = NULL) {
    $this->dashboard = $this->buildDashboard($did);

    $active_theme = $this->themeManager->getActiveTheme();
    $theme_name = $active_theme->getName();

    $form['#title'] = $this->dashboard->get('title');
    $form['did'] = [
      '#type' => 'hidden',
      '#value' => $this->dashboard->id(),
    ];
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'draggable_dashboard/draggable_dashboard.main';

    $form['dashboard_blocks_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block'),
        $this->t('Category'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No columns defined.'),
      '#attributes' => array(
        'id' => 'dashboardblocks',
      ),
    ];

    // prepare available regions list
    $regionOptions = [];
    for ($i = 1; $i <= $this->dashboard->get('columns'); $i++) {
      $regionOptions[$i] = $i . ' ' . t('Column');
    }

    $entities = json_decode($this->dashboard->get('blocks'), true);

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = round(count($entities) / 2);

    for ($i = 1; $i <= $this->dashboard->get('columns'); $i++) {
      $region = $i;
      $title = t('Column') . ' #' . $i;
      $form['dashboard_blocks_table']['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      ];
      $form['dashboard_blocks_table']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      ];
      $form['dashboard_blocks_table']['region-' . $region] = [
        '#attributes' => [
          'class' => ['region-title', 'region-title-' . $region],
          'no_striping' => TRUE,
        ],
      ];
      $form['dashboard_blocks_table']['region-' . $region]['title'] = [
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => 'region-title__action'],
          ]
        ],
        '#prefix' => $title,
        '#type' => 'link',
        '#title' => t('Place block <span class="visually-hidden">in the %region region</span>', ['%region' => $title]),
        '#url' => Url::fromRoute('block.admin_library', ['theme' => $theme_name], [
          'query' => [
            'region' => 'draggable_dashboard-' . base64_encode(json_encode([
                'did' => $did,
                'cln' => $region
              ]))
          ]
        ]),
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small', 'place-blocks'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];

      // collect all blocks from one particular column
      $relations = [];
      if (!empty($entities)){
        foreach ($entities as $entity){
          if ($entity['cln'] == $i){
            $relations[] = $entity;
          }
        }
      }

      $form['dashboard_blocks_table']['region-' . $region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            empty($relations) ? 'region-empty' : 'region-populated',
          ],
        ],
      ];
      $form['dashboard_blocks_table']['region-' . $region . '-message']['message'] = [
        '#markup' => '<em>' . t('No blocks in this region') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      if (!empty($relations)) {

        foreach ($relations as $delta => $relation) {

          $block = Block::load($relation['bid']);
          if (empty($block)) {
            continue;
          }
          $block->set('region', DashboardEntityInterface::BASE_REGION_NAME);
          $block->set('theme', 'seven');
          $block->save();

          $entity_id = $relation['bid'];

          $form['dashboard_blocks_table'][$entity_id] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
          ];
          $form['dashboard_blocks_table'][$entity_id]['info'] = [
            '#plain_text' => $block->label(),
            '#wrapper_attributes' => [
              'class' => ['block'],
            ],
          ];
          $form['dashboard_blocks_table'][$entity_id]['type'] = [
            '#markup' => $block->getPluginId(),
          ];
          $form['dashboard_blocks_table'][$entity_id]['region-theme']['region'] = [
            '#type' => 'select',
            '#default_value' => $relation['cln'],
            '#required' => TRUE,
            '#title' => $this->t('Region for @block block', ['@block' => $block->label()]),
            '#title_display' => 'invisible',
            '#options' => $regionOptions,
            '#attributes' => [
              'class' => ['block-region-select', 'block-region-' . $region],
            ],
            '#parents' => ['blocks', $entity_id, 'region'],
          ];
          $form['dashboard_blocks_table'][$entity_id]['weight'] = [
            '#type' => 'weight',
            '#default_value' => $relation['position'],
            '#delta' => $weight_delta,
            '#title' => t('Weight for @block block', ['@block' => $block->label()]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-weight', 'block-weight-' . $region],
            ],
          ];

          $links = [];
          $links['edit'] = [
            'title' => $this->t('Configure'),
            'url' => Url::fromRoute('entity.block.edit_form', ['block' => $block->id()]),
          ];
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('draggable_dashboard.delete_block', [
              'did' => $did,
              'bid' => $relation['bid']
            ]),
          ];
          $form['dashboard_blocks_table'][$entity_id]['operations'] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links
            ]
          ];
        }
      }
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['actions']['back'] = [
      '#type' => 'link',
      '#title' => $this->t('Back To Dashboards'),
      '#url' => new Url('draggable_dashboard.overview'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove unnecessary values.
    $form_state->cleanValues();

    $did = $form_state->getValue('did', 0);

    // Save Dashboard Blocks
    $dBlocks = [];
    $blocks = $form_state->getValue('blocks', []);
    $table = $form_state->getValue('dashboard_blocks_table', []);
    foreach ($blocks as $id => $block) {
      $position = 0;
      foreach ($table as $key => $new_position){
        if ($id == $key){
          break;
        }
        if ($block['region'] == $blocks[$key]['region']){
          $position++;
        }
      }
      $dBlocks[] = [
        'bid' => $id,
        'cln' => $block['region'],
        'position' => (int) $position
      ];
    }

    $this->dashboard->set('blocks', json_encode($dBlocks))->save();

    drupal_set_message($this->t('Dashboard blocks has been updated.'));

    $form_state->setRedirect('draggable_dashboard.manage_dashboard', ['did' => $did]);
  }
}
