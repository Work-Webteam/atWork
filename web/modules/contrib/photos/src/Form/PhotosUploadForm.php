<?php

namespace Drupal\photos\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\photos\PhotosUpload;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to upload photos to this site.
 */
class PhotosUploadForm extends FormBase {

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
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_manager, ImageFactory $image_factory, ModuleHandlerInterface $module_handler, RouteMatchInterface $route_match) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_manager;
    $this->imageFactory = $image_factory;
    $this->logger = $this->getLogger('photos');
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('image.factory'),
      $container->get('module_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photos_upload';
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Check if user can edit this album.
    $node = $this->routeMatch->getParameter('node');
    if (_photos_access('editAlbum', $node)) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('photos.settings');
    // Get node object.
    $node = $this->routeMatch->getParameter('node');
    $nid = $node->id();

    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['new'] = [
      '#title' => $this->t('Image upload'),
      '#weight' => -4,
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $allow_zip = (($config->get('photos_upzip')) ? ' zip' : '');
    // Check if plubload is installed.
    if ($config->get('photos_plupload_status')) {
      $form['new']['plupload'] = [
        '#type' => 'plupload',
        '#title' => $this->t('Upload photos'),
        '#description' => $this->t('Upload multiple images.'),
        '#autoupload' => TRUE,
        '#submit_element' => '#edit-submit',
        '#upload_validators' => [
          'file_validate_extensions' => ['jpg jpeg gif png' . $allow_zip],
        ],
        '#plupload_settings' => [
          'chunk_size' => '1mb',
        ],
      ];
    }
    else {
      // Manual upload form.
      $form['new']['#description'] = $this->t('Allowed types: jpg gif png jpeg@zip', ['@zip' => $allow_zip]);

      for ($i = 0; $i < $config->get('photos_num'); ++$i) {
        $form['new']['images_' . $i] = [
          '#type' => 'file',
        ];
        $form['new']['title_' . $i] = [
          '#type' => 'textfield',
          '#title' => $this->t('Image title'),
        ];
        $form['new']['des_' . $i] = [
          '#type' => 'textarea',
          '#title' => $this->t('Image description'),
          '#cols' => 40,
          '#rows' => 3,
        ];
      }
    }
    // @todo pid is redundant unless albums become own entity.
    //   - maybe make pid serial and add nid... or entity_id.
    $form['new']['pid'] = [
      '#type' => 'value',
      '#value' => $nid,
    ];
    $form['new']['nid'] = [
      '#type' => 'value',
      '#value' => $nid,
    ];
    $form['new']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm upload'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // ...
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $config = $this->config('photos.settings');
    $validators = [
      'file_validate_is_image' => [],
    ];
    $count = 0;
    $nid = $form_state->getValue('nid');
    $album_uid = $this->connection->query("SELECT uid FROM {node_field_data} WHERE nid = :nid", [':nid' => $nid])->fetchField();
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
              better security, ask your website admin to setup a private file
              path.'));
          }
        }
      }
    }
    if (empty($album_uid)) {
      $album_uid = $user->id();
    }
    $account = $this->entityTypeManager->getStorage('user')->load($album_uid);
    // Check if plupload is enabled.
    // @todo check for plupload library?
    if ($config->get('photos_plupload_status')) {
      $plupload_files = $form_state->getValue('plupload');
      foreach ($plupload_files as $uploaded_file) {
        if ($uploaded_file['status'] == 'done') {
          // Check for zip files.
          $ext = mb_substr($uploaded_file['name'], -3);
          if ($ext <> 'zip' && $ext <> 'ZIP') {
            // Prepare directory.
            $photos_path = PhotosUpload::path($scheme, '', $account);
            $photos_name = PhotosUpload::rename($uploaded_file['name']);
            $file_uri = \Drupal::service('file_system')
              ->getDestinationFilename($photos_path . '/' . $photos_name, FileSystemInterface::EXISTS_RENAME);
            if (\Drupal::service('file_system')->move($uploaded_file['tmppath'], $file_uri)) {
              $path_parts = pathinfo($file_uri);
              $image = $this->imageFactory->get($file_uri);
              if ($path_parts['extension'] && $image->getWidth()) {
                // Create a file entity.
                $file = $this->entityTypeManager->getStorage('file')->create([
                  'uri' => $file_uri,
                  'uid' => $user->id(),
                  'status' => FILE_STATUS_PERMANENT,
                  'pid' => $form_state->getValue('pid'),
                  'nid' => $form_state->getValue('nid'),
                  'filename' => $photos_name,
                  'filesize' => $image->getFileSize(),
                  'filemime' => $image->getMimeType(),
                ]);

                if (PhotosUpload::saveFile($file)) {
                  PhotosUpload::saveImage($file);
                }
                $count++;
              }
              else {
                \Drupal::service('file_system')->delete($file_uri);
                $this->logger->notice('Wrong file type');
              }
            }
            else {
              $this->logger->notice('Upload error. Could not move temp file.');
            }
          }
          else {
            if (!$config->get('photos_upzip')) {
              \Drupal::messenger()->addError($this->t('Please set Album
                photos to open zip uploads.'));
            }
            $directory = PhotosUpload::path();
            \Drupal::service('file_system')->prepareDirectory($directory);
            $zip = \Drupal::service('file_system')
              ->getDestinationFilename($directory . '/' . $uploaded_file['name'], FileSystemInterface::EXISTS_RENAME);
            if (\Drupal::service('file_system')->move($uploaded_file['tmppath'], $zip)) {
              $value = new \StdClass();
              $value->pid = $form_state->getValue('pid');
              $value->nid = $form_state->getValue('nid');
              $value->title = $uploaded_file['name'];
              $value->des = '';
              // Unzip it.
              if (!$file_count = PhotosUpload::unzip($zip, $value, $scheme, $account)) {
                \Drupal::messenger()->addError($this->t('Zip upload failed.'));
              }
              else {
                // Update image upload count.
                $count = $count + $file_count;
              }
            }
          }
        }
        else {
          \Drupal::messenger()->addError($this->t('Error uploading some photos.'));
        }
      }
    }
    else {
      // Manual upload form.
      $pid = $form_state->getValue('pid');
      $photos_num = $config->get('photos_num');
      for ($i = 0; $i < $photos_num; ++$i) {
        if (isset($_FILES['files']['name']['images_' . $i]) && $_FILES['files']['name']['images_' . $i]) {
          $ext = mb_substr($_FILES['files']['name']['images_' . $i], -3);
          if ($ext <> 'zip' && $ext <> 'ZIP') {
            // Prepare directory.
            $photos_path = PhotosUpload::path($scheme, '', $account);
            if ($file = file_save_upload('images_' . $i, $validators, $photos_path, 0)) {
              // Save file to album. Include title and description.
              $file->pid = $pid;
              $file->nid = $form_state->getValue('nid');
              $file->des = $form_state->getValue('des_' . $i);
              $file->title = $form_state->getValue('title_' . $i);
              PhotosUpload::saveImage($file);
              $count++;
            }
          }
          else {
            // Zip upload from manual upload form.
            if (!$config->get('photos_upzip')) {
              \Drupal::messenger()->addError($this->t('Please update settings to allow zip uploads.'));
            }
            else {
              $directory = PhotosUpload::path();
              \Drupal::service('file_system')->prepareDirectory($directory);
              $zip = \Drupal::service('file_system')
                ->getDestinationFilename($directory . '/' . trim(basename($_FILES['files']['name']['images_' . $i])), FileSystemInterface::EXISTS_RENAME);
              if (\Drupal::service('file_system')->move($_FILES['files']['tmp_name']['images_' . $i], $zip)) {
                $value = new \stdClass();
                $value->pid = $pid;
                $value->nid = $form_state->getValue('nid') ? $form_state->getValue('nid') : $form_state->getValue('pid');
                $value->des = $form_state->getValue('des_' . $i);
                $value->title = $form_state->getValue('title_' . $i);
                if (!$file_count = PhotosUpload::unzip($zip, $value, $scheme, $account)) {
                  // Upload failed.
                }
                else {
                  $count = $count + $file_count;
                }
              }
            }
          }
        }
      }
    }
    // Clear node and album page cache.
    Cache::invalidateTags(['node:' . $nid, 'photos:album:' . $nid]);
    $message = $this->formatPlural($count, '1 image uploaded.', '@count images uploaded.');
    \Drupal::messenger()->addMessage($message);
  }

}
