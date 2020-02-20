<?php

namespace Drupal\photos\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a confirmation form for deleting images.
 */
class PhotosImageDeleteForm extends ConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $id;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photos_image_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete this image?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // @todo check destination?
    $url = Url::fromUri('base:photos/image/' . $this->id);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Nevermind');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int $file
   *   (optional) The ID of the item to be deleted.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $file = '') {
    // @todo update access!
    $this->id = $file;
    if (!$this->id) {
      throw new NotFoundHttpException();
    }
    // @todo set album type?
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $this->id;
    $pid = $this->connection->query("SELECT pid FROM {photos_image} WHERE fid = :fid", [':fid' => $fid])->fetchField();

    // Delete image.
    $image = new PhotosImage($fid);
    $v = $image->delete(NULL, TRUE);

    if ($v) {
      \Drupal::messenger()->addMessage(t('Image deleted.'));
      // Invalidate cache tags.
      Cache::invalidateTags([
        'node:' . $pid, 'photos:album:' . $pid,
        'photos:image:' . $fid,
      ]);
      // @todo redirect to album.
      $url = Url::fromUri('base:photos/album/' . $pid);
      $form_state->setRedirectUrl($url);
    }
    else {
      \Drupal::messenger()->addError(t('Delete failed.'));
      // Redirect to cancel URL.
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

}
