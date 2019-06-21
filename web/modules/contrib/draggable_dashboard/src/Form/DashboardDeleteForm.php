<?php

namespace Drupal\draggable_dashboard\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\draggable_dashboard\Entity\DashboardEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to delete Dashboard Form.
 */
class DashboardDeleteForm extends ConfirmFormBase {

  /**
   * The Dashboard.
   *
   * @var \Drupal\draggable_dashboard\Entity\DashboardEntity
   */
  protected $dashboard;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   *
   * DashboardDeleteForm constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   */
  public function __construct(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'draggable_dashboard_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want delete dashboard `%title`?', ['%title' => $this->dashboard->get('title')]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('draggable_dashboard.overview');
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $did
   *   The Dashboard record ID to delete.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $did = '') {
    if (!$this->dashboard = DashboardEntity::load($did)) {
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $blocks = json_decode($this->dashboard->get('blocks'), TRUE);
    foreach ($blocks as $relation){
      $block = Block::load($relation['bid']);
      if ($block){
        $block->delete();
      }
    }
    $this->logger('user')
      ->notice('Deleted `%title`', ['%title' => $this->dashboard->get('title')]);
    drupal_set_message($this->t('The dashboard `%title` was deleted.', ['%title' => $this->dashboard->get('title')]));
    // delete dashboard
    $this->dashboard->delete();
    // invalidate block list cache
    $this->blockManager->clearCachedDefinitions();
    // redirect to dashboard overview page
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
