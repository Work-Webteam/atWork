<?php

namespace Drupal\photos_access\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a form to upload photos to this site.
 */
class PhotosAccessPasswordForm extends FormBase {

  /**
   * The database connection used to check the password.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct the PhotosAccessPasswordForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to check the password.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photos_access_password';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Please enter album password'),
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];
    $form['nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $node = $this->connection->query("SELECT pass, nid FROM {photos_access_album} WHERE nid = :nid AND pass = :pass",
      [
        ':nid' => $form_state->getValue('nid'),
        ':pass' => md5($form_state->getValue('pass')),
      ]
    )->fetchObject();
    if (isset($node->pass)) {
      $_SESSION[$node->nid . '_' . session_id()] = $node->pass;

      // Redirect.
      $redirect_url = Url::fromUri('base:node/' . $node->nid)->toString();
      return new RedirectResponse($redirect_url);
    }
    else {
      $form_state->setErrorByName('pass', $this->t('Password required'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // ...
  }

}
