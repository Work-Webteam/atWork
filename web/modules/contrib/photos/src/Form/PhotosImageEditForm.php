<?php

namespace Drupal\photos\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\photos\PhotosAlbum;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to edit images.
 */
class PhotosImageEditForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photos_image_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $image = NULL, $type = 'album') {
    $user = $this->currentUser();

    // Get node object.
    $node = $this->entityTypeManager->getStorage('node')->load($image->pid);
    $nid = $node->id();
    $cover = isset($node->album['cover']) ? $node->album['cover'] : [];
    $image->info = [
      'cover' => $cover,
      'pid' => $node->id(),
      'title' => $node->getTitle(),
      'uid' => $node->getOwnerId(),
    ];

    if ($node->getType() == 'photos') {
      // Album.
      $album_update = '';
      if ($image && $user->id() <> $image->info['uid']) {
        $title = isset($image->info['title']) ? $image->info['title'] : '';
        $album_update = [$nid, $image->info['title']];
      }
      else {
        $album_update = '';
      }
      $uid = $image ? $image->uid : $user->id();
      $album_pid = PhotosAlbum::userAlbumOptions($uid, $album_update);
      $del_label = $this->t('Delete');
      if (isset($node->album) && isset($node->album['cover']['fid'])) {
        $form['cover_fid'] = ['#type' => 'hidden', '#default_value' => $node->album['cover']['fid']];
      }
      $form['oldpid'] = ['#type' => 'hidden', '#default_value' => $nid];
    }
    $form['nid'] = ['#type' => 'hidden', '#default_value' => $nid];
    $form['type'] = ['#type' => 'hidden', '#value' => $type];
    $form['fid'] = ['#type' => 'hidden', '#value' => $image->fid];
    $form['del'] = [
      '#title' => $del_label,
      '#type' => 'checkbox',
    ];
    $image->user = $this->entityTypeManager->getStorage('user')->load($image->uid);
    $image->href = 'photos/image/' . $image->fid;
    $item = [];
    $title = $image->title;
    $image_sizes = $this->config('photos.settings')->get('photos_size');
    $style_name = key($image_sizes);
    $image_view = [
      '#theme' => 'image_style',
      '#style_name' => $style_name,
      '#uri' => $image->uri,
      '#alt' => $title,
      '#title' => $title,
    ];

    $item[] = Link::fromTextAndUrl($image_view, Url::fromUri('base:' . $image->href), [
      'html' => TRUE,
      'attributes' => ['title' => $title],
    ]);

    if ($type == 'album' && (!isset($cover['fid']) || isset($cover['fid']) && $image->fid <> $cover['fid'])) {
      // Set cover link.
      $cover_url = Url::fromRoute('photos.album.update.cover', [
        'node' => $image->pid,
        'file' => $image->fid,
      ]);
      $item[] = Link::fromTextAndUrl($this->t('Set to Cover'), $cover_url);
    }
    if (isset($image->filesize)) {
      // @todo update to use MB?
      $size = round($image->filesize / 1024);
      $item[] = $this->t('Filesize: @size KB', ['@size' => number_format($size)]);
    }
    if (isset($image->count)) {
      $item[] = $this->t('Visits: @count', ['@count' => $image->count]);
    }
    if (isset($image->comcount)) {
      $item[] = $this->t('Comments: @count', ['@count' => $image->comcount]);
    }
    $form['title'] = [
      '#title' => $this->t('Image title'),
      '#type' => 'textfield',
      '#default_value' => isset($image->title) ? $image->title : '',
      '#required' => FALSE,
    ];
    $form['path'] = [
      '#theme' => 'item_list',
      '#items' => $item,
    ];
    // Check for cropper module and add image_crop field.
    if ($this->moduleHandler->moduleExists('image_widget_crop') &&
      $crop_config = $this->config('image_widget_crop.settings')) {
      if ($crop_config->get('settings.crop_list')) {
        $file = $this->entityTypeManager->getStorage('file')->load($image->fid);
        // @todo move to form alter along with submit handler.
        $form['image_crop'] = [
          '#type' => 'image_crop',
          '#file' => $file,
          '#crop_type_list' => $crop_config->get('settings.crop_list'),
          '#crop_preview_image_style' => $crop_config->get('settings.crop_preview_image_style'),
          '#show_default_crop' => $crop_config->get('settings.show_default_crop'),
          '#warn_mupltiple_usages' => $crop_config->get('settings.warn_mupltiple_usages'),
        ];
      }
    }
    $form['des'] = [
      '#title' => $this->t('Image description'),
      '#type' => 'textarea',
      '#default_value' => isset($image->des) ? $image->des : '',
      '#cols' => 40,
      '#rows' => 4,
    ];
    $form['wid'] = [
      '#title' => $this->t('Weight'),
      '#type' => 'textfield',
      '#size' => 5,
      '#default_value' => isset($image->wid) ? $image->wid : NULL,
    ];
    $form['filepath'] = ['#type' => 'value', '#value' => $image->uri];
    if ($type == 'album') {
      $username = [
        '#theme' => 'username',
        '#account' => $image->user,
      ];
      $upload_info = $this->t('Uploaded on @time by @name', [
        '@name' => $this->renderer->renderPlain($username),
        '@time' => $this->dateFormatter->format($image->created, 'short'),
      ]);
      $form['pid'] = [
        '#title' => $this->t('Move to album'),
        '#type' => 'select',
        '#options' => $album_pid,
        '#default_value' => $image->pid,
        '#required' => TRUE,
      ];
    }
    else {
      $upload_info = $this->t('Uploaded by @name on @time to @title', [
        '@name' => [
          '#theme' => 'username',
          '#account' => $image->user,
        ],
        '@time' => $this->dateFormatter->format($image->created, 'short'),
        '@title' => Link::fromTextAndUrl($image->album_title, Url::fromUri('base:node/' . $image->pid)),
      ]);
    }
    $form['time']['#markup'] = $upload_info;
    $form['uid'] = ['#type' => 'hidden', '#default_value' => $image->uid];
    $form['oldtitle'] = ['#type' => 'hidden', '#default_value' => $image->title];
    if (!empty($image)) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm changes'),
      ];
    }

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
    // Process image cropping data.
    $form_state_values = $form_state->getValues();
    $fid = $form_state_values['fid'];
    $old_pid = $form_state_values['oldpid'];
    $pid = $form_state_values['pid'];
    $uid = $form_state_values['uid'];
    // Save other image data.
    if (!empty($form_state_values['del'])) {
      if ($form_state->getValue('cover_fid') == $fid) {
        $this->connection->update('photos_album')
          ->fields([
            'fid' => 0,
          ])
          ->condition('pid', $form_state->getValue('oldpid'))
          ->execute();
      }
      $image = new PhotosImage($fid);
      $msg = $image->delete($form_state_values['filepath']);
    }
    else {
      $wid = is_numeric($form_state_values['wid']) ? $form_state_values['wid'] : 0;
      $this->connection->update('photos_image')
        ->fields([
          'pid' => $form_state_values['pid'],
          'des' => $form_state_values['des'],
          'wid' => $wid,
        ])
        ->condition('fid', $fid)
        ->execute();

      if ($form_state_values['title'] <> $form_state_values['oldtitle']) {
        $this->connection->update('photos_image')
          ->fields([
            'title' => $form_state_values['title'],
          ])
          ->condition('fid', $fid)
          ->execute();
      }
    }
    // Clear image page cache.
    Cache::invalidateTags(['photos:image:' . $fid]);
    if ($nid = $form_state->getValue('nid')) {
      // Clear album page and node cache.
      Cache::invalidateTags(['photos:album:' . $nid, 'node:' . $nid]);
    }

    if (isset($pid) && $pid) {
      $pid;
      // Update album count.
      PhotosAlbum::setCount('node_album', $pid);
      // Clear album page and node cache.
      Cache::invalidateTags(['photos:album:' . $pid, 'node:' . $pid]);
      PhotosAlbum::setCount('user_image', $uid);
      if ($old_pid && $old_pid <> $pid) {
        // Update old album count.
        PhotosAlbum::setCount('node_album', $old_pid);
        // Clear old album page and node cache.
        Cache::invalidateTags(['photos:album:' . $old_pid, 'node:' . $old_pid]);
      }
    }

    // Image deleted or moved.
    if (isset($msg)) {
      $pid = $form_state->getValue('oldpid');
      \Drupal::messenger()->addMessage($this->t('Image deleted.'));
      // Redirect to album page.
      $nid = $form_state->getValue('nid');
      $url = Url::fromUri('base:photos/album/' . $nid);
      $form_state->setRedirectUrl($url);
    }
    // @todo redirect to image page?
    // @todo redirect to destination.
    if (empty($form_state_values['del'])) {
      \Drupal::messenger()->addMessage($this->t('Changes saved.'));
    }

  }

}
