<?php

/**
 * @file
 * Contains Drupal\user_alert\Form\UserAlertTypeForm.
 */

namespace Drupal\user_alert\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserAlertTypeForm.
 *
 * @package Drupal\user_alert\Form
 */
class UserAlertTypeForm extends BundleEntityFormBase {
  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the NodeTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $type = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $type->label(),
      '#description' => $this->t("Label for the User alert type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\user_alert\Entity\UserAlertType::load',
      ),
      '#disabled' => !$type->isNew(),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new user alert</em> page.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $user_alert_type = $this->entity;
    $status = $user_alert_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label User alert type.', [
          '%label' => $user_alert_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label User alert type.', [
          '%label' => $user_alert_type->label(),
        ]));
    }

    $form_state->setRedirectUrl($user_alert_type->urlInfo('collection'));
  }

}
