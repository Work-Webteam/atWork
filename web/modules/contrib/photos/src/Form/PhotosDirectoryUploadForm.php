<?php

namespace Drupal\photos\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to upload photos to this site.
 */
class PhotosDirectoryUploadForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photos_directory_upload';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $submit_text = $this->t('Move images');
    $show_submit = TRUE;
    // Add warning that images will be moved to the album's directory.
    $instructions = $this->t('Add photos to an album from a directory that is already on the server. First choose a user.
                     Then select an album. Then enter the directory where the photos are located. Note that the photos
                     will be moved to the selected albums directory. Warning: large zip files could fail depending on
                     server processing power. If it does fail, try unzipping the folders and running the batch again.');
    $form['instructions'] = [
      '#markup' => '<div>' . $instructions . '</div>',
    ];
    if ($uid = $form_state->getValue('user')) {
      // Look up user albums and generate options for select list.
      $albums = $this->connection->query("SELECT nid, title FROM {node_field_data} WHERE uid = :uid AND type = 'photos'", [':uid' => $uid]);
      $options = [];
      foreach ($albums as $album) {
        $options[$album->nid] = '[nid:' . $album->nid . '] ' . $album->title;
      }
      if (empty($options)) {
        // No albums found for selected user.
        $add_album_link = Link::fromTextAndUrl($this->t('Add new album.'), Url::fromUri('base:node/add/photos'))->toString();
        $form['add_album'] = [
          '#markup' => '<div>' . $this->t('No albums found.') . ' ' . $add_album_link . '</div>',
        ];
        $show_submit = FALSE;
      }
      else {
        // Select album.
        $form['uid'] = ['#type' => 'hidden', '#value' => $uid];
        $form['album'] = [
          '#type' => 'select',
          '#title' => $this->t('Select album'),
          '#options' => $options,
        ];
        // Directory.
        $form['directory'] = [
          '#title' => $this->t('Directory'),
          '#type' => 'textfield',
          '#required' => TRUE,
          '#default_value' => '',
          '#description' => $this->t('Directory containing images. Include / for absolute path. Include
            public:// or private:// to scan a directory in the public or private filesystem.'),
        ];
        // Copy.
        $form['copy'] = [
          '#title' => $this->t('Copy files instead of moving them.'),
          '#type' => 'checkbox',
          '#default_value' => 0,
        ];
      }
    }
    else {
      // User autocomplete.
      $form['user'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Username'),
        '#description' => $this->t('Enter a user name.'),
        '#target_type' => 'user',
        '#tags' => FALSE,
        '#required' => TRUE,
        '#default_value' => '',
        '#process_default_value' => FALSE,
      ];
      $submit_text = $this->t('Select user');
    }

    if ($show_submit) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $submit_text,
        '#weight' => 10,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $directory = $form_state->getValue('directory');
    // Check if directory exists.
    if (!empty($directory) && !is_dir($directory)) {
      return $form_state->setErrorByName('directory', $this->t('Could not find directory. Please check the path.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('photos.settings');
    $user_value = $form_state->getValue('user');
    $copy = $form_state->getValue('copy');
    if ($user_value) {
      $form_state->setRebuild();
    }
    else {
      // @todo check if file is already in use before moving?
      // - If in use copy?
      $album = $form_state->getValue('album');
      $directory = $form_state->getValue('directory');
      $nid = $album;
      $album_uid = $form_state->getValue('uid');
      // If photos_access is enabled check viewid.
      $scheme = 'default';
      $album_viewid = 0;
      if ($this->moduleHandler->moduleExists('photos_access')) {
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        if (isset($node->privacy) && isset($node->privacy['viewid'])) {
          $album_viewid = $node->privacy['viewid'];
          if ($album_viewid > 0) {
            // Check for private file path.
            if (PrivateStream::basePath()) {
              $scheme = 'private';
            }
            else {
              // Set warning message.
              \Drupal::messenger()->addWarning($this->t('Warning: image
                files can still be accessed by visiting the direct URL. For
                better security, ask your website admin to setup a private
                file path.'));
            }
          }
        }
      }
      $account = $this->entityTypeManager->getStorage('user')->load($album_uid);
      // Check if zip is included.
      $allow_zip = $config->get('photos_upzip') ? '|zip|ZIP' : '';
      $file_extensions = 'png|PNG|jpg|JPG|jpeg|JPEG|gif|GIF' . $allow_zip;
      $files = file_scan_directory($directory, '/^.*\.(' . $file_extensions . ')$/');

      // Prepare batch.
      $batch_args = [
        $files,
        $account,
        $nid,
        $scheme,
        $allow_zip,
        $file_extensions,
        $copy,
      ];
      $batch = [
        'title' => $this->t('Moving images to gallery'),
        'operations' => [
          ['\Drupal\photos\PhotosUpload::moveImageFiles', $batch_args],
        ],
        'finished' => '\Drupal\photos\PhotosUpload::finishedMovingImageFiles',
      ];
      batch_set($batch);
    }
  }

}
