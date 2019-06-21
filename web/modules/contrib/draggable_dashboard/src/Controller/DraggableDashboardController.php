<?php

namespace Drupal\draggable_dashboard\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\draggable_dashboard\Entity\DashboardEntity;
use Drupal\draggable_dashboard\Entity\DashboardEntityInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for draggable dashboards.
 */
class DraggableDashboardController extends ControllerBase {

  /**
   * Displays the draggable dashboard administration overview page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminOverview(Request $request) {

    $rows = [];
    $header = [$this->t('Title'), $this->t('Description'), $this->t('Operations')];

    $dashboards = \Drupal::entityQuery('dashboard_entity')->execute();

    foreach ($dashboards as $dashboardID) {
      $dashboard = DashboardEntity::load($dashboardID);
      $row = [];
      $row[] = $dashboard->get('title');
      $row[] = $dashboard->get('description');
      $links = [
          'manage' => [
              'title' => $this->t('Manage Blocks'),
              'url' => Url::fromRoute('draggable_dashboard.manage_dashboard', ['did' => $dashboard->id()]),
          ],
          'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('draggable_dashboard.edit_dashboard', ['did' => $dashboard->id()]),
          ],
          'delete' => [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('draggable_dashboard.delete_dashboard', ['did' => $dashboard->id()]),
          ],
      ];
      $row[] = ['data' => ['#type' => 'operations', '#links' => $links]];
      $rows[] = $row;
    }

    $form['dashboard_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No draggable dashboards available.'),
      '#weight' => 120,
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function assignBlock(array &$form, FormStateInterface $form_state){
      // save block entity
      $settings = $form_state->getValue('settings');
      $region = $form_state->getValue('region');
      $dashboard_id = $form_state->getValue('dashboard_id');
      $block_id = $form_state->getValue('id');
      $obj = $form_state->getBuildInfo()['callback_object'];
      /** @var Block $block */
      $block = $obj->getEntity();
      $block->set('id', $block_id);
      $block->set('region', DashboardEntityInterface::BASE_REGION_NAME);
      $block->set('settings', $settings);
      $block->enable();
      $block->save();
      /** @var DashboardEntity $dashboard */
      $dashboard = DashboardEntity::load($dashboard_id);
      $blocks = json_decode($dashboard->get('blocks'), true);
      $relationFounded = false;
      if (!empty($blocks)) {
        foreach ($blocks as $key => $relation){
          if ($relation['bid'] == $block_id){
            $blocks[$key]['cln'] = (int) $region;
            $relationFounded = TRUE;
            break;
          }
        }
      }
      if (!$relationFounded){
        $blocks[] = [
          'bid' => $block_id,
          'cln' => (int) $region,
          'position' => 0
        ];
      }
      // save relation
      $dashboard->set('blocks', json_encode($blocks))->save();
      // redirect to manage blocks screen
      $form_state->setRedirect('draggable_dashboard.manage_dashboard', ['did' => $dashboard_id]);
  }

  /**
   * @param $did
   * @param $bid
   * @return RedirectResponse
   */
  public function deleteBlock($did, $bid){
    /** @var DashboardEntity $dashboard */
    $dashboard = DashboardEntity::load($did);
    $blocks = json_decode($dashboard->get('blocks'), TRUE);
    if (!empty($blocks)){
      foreach ($blocks as $key => $relation){
        if ($relation['bid'] == $bid){
          $block = Block::load($relation['bid']);
          $block->delete();
          unset($blocks[$key]);
        }
      }
    }
    // delete block relation
    $dashboard->set('blocks', json_encode($blocks))->save();
    $manageURL = Url::fromRoute('draggable_dashboard.manage_dashboard', ['did' => $did]);
    $response = new RedirectResponse($manageURL->toString());
    return $response->send();
  }

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function updateBlockPositions(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    $did = $request->request->get('did');
    $blocks = $request->request->get('blocks');

    /** @var DashboardEntity $dashboard */
    $dashboard = DashboardEntity::load($did);
    $dBlocks = json_decode($dashboard->get('blocks'), TRUE);

    if (!isset($blocks) && empty($dashboard)) {
      throw new NotFoundHttpException();
    }

    // Update dashboard blocks positions
    foreach ($dBlocks as $key => $dBlock){
      foreach ($blocks as $bid => $relation){
        if ($dBlock['bid'] == $bid){
          $dBlocks[$key]['cln'] = $relation['region'];
          $dBlocks[$key]['position'] = $relation['order'];
        }
      }
    }
    $dashboard->set('blocks', json_encode($dBlocks))->save();

    return new JsonResponse(['success' => TRUE]);
  }
}
