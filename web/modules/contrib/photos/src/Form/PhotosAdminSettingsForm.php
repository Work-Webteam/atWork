<?php

namespace Drupal\photos\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\Url;
use Drupal\photos\PhotosAlbum;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class PhotosAdminSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photos_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Get variables for default values.
    $config = $this->config('photos.settings');

    // Load custom admin css and js library.
    $form['#attached']['library'] = [
      'photos/photos.admin',
    ];

    $form['basic'] = [
      '#title' => $this->t('Basic settings'),
      '#weight' => -5,
      '#type' => 'details',
    ];

    // Photos access integration settings.
    $module_photos_access_exists = $this->moduleHandler->moduleExists('photos_access');
    $url = Url::fromRoute('system.modules_list', [], ['fragment' => 'module-photos-access']);
    $link = Link::fromTextAndUrl('photos_access', $url)->toString();
    $warning_msg = '';
    // Set warning if private file path is not set.
    if (!PrivateStream::basePath() && $config->get('photos_access_photos')) {
      $warning_msg = $this->t('Warning: image files can still be accessed by visiting the direct URL.
        For better security, ask your website admin to setup a private file path.');
    }
    $form['basic']['photos_access_photos'] = [
      '#type' => 'radios',
      '#title' => $this->t('Privacy settings'),
      '#default_value' => $config->get('photos_access_photos') ?: 0,
      '#description' => $module_photos_access_exists ? $warning_msg : $this->t('Enable the @link module.', ['@link' => $link]),
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
      '#required' => TRUE,
      '#disabled' => ($module_photos_access_exists ? FALSE : TRUE),
    ];

    // Classic upload form settings.
    $num_options = [
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6,
      7 => 7,
      8 => 8,
      9 => 9,
      10 => 10,
    ];
    $form['basic']['photos_num'] = [
      '#type' => 'select',
      '#title' => $this->t('Classic form'),
      '#default_value' => $config->get('photos_num'),
      '#required' => TRUE,
      '#options' => $num_options,
      '#description' => $this->t('Maximum number of upload fields on the classic upload form.'),
    ];

    // Plupload integration settings.
    $module_plupload_exists = $this->moduleHandler->moduleExists('plupload');
    if ($module_plupload_exists) {
      $form['basic']['photos_plupload_status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Plupoad for file uploads'),
        '#default_value' => $config->get('photos_plupload_status'),
      ];
    }
    else {
      $config->set('photos_plupload_status', 0)->save();
      $form['basic']['photos_plupload_status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Plupoad for file uploads'),
        '#disabled' => TRUE,
        '#description' => $this->t('To enable multiuploads and drag&amp;drop upload features, download and install the @link module', [
          '@link' => Link::fromTextAndUrl($this->t('Plupload integration'), Url::fromUri('http://drupal.org/project/plupload'))->toString(),
        ]),
      ];
    }

    // Check if colorbox is enabled.
    $colorbox = FALSE;
    if ($this->moduleHandler->moduleExists('colorbox')) {
      $colorbox = TRUE;
    }

    // Photos module settings.
    // @todo token integration.
    $form['basic']['photos_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#default_value' => $config->get('photos_path'),
      '#description' => $this->t('The path where the files will be saved relative to the files folder.
        Available variables: %uid, %username, %Y, %m, %d.'),
      '#size' => '40',
      '#required' => TRUE,
    ];
    $form['basic']['photos_size_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum image resolution'),
      '#default_value' => $config->get('photos_size_max'),
      '#description' => $this->t('The maximum image resolution example: 800x600. If an image toolkit is available the image will be scaled
        to fit within the desired maximum dimensions. Make sure this size is larger than any image styles used.
        Leave blank for no restrictions.'),
      '#size' => '40',
    ];
    $form['basic']['photos_comment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Comment setting'),
      '#default_value' => $config->get('photos_comment') ?: 0,
      '#description' => $this->t('Enable to comment on single photo. You must also open comments for content type / node.'),
      '#required' => TRUE,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
    ];

    $form['basic']['photos_upzip'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow zip upload'),
      '#default_value' => $config->get('photos_upzip') ?: 0,
      '#description' => $this->t('Will be allowed to upload images compressed into a zip folder.'),
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
    ];
    // @todo look into transliteration integration D8 core.
    $form['basic']['photos_rname'] = [
      '#type' => 'radios',
      '#title' => $this->t('Rename image'),
      '#default_value' => $config->get('photos_rname') ?: 0,
      '#description' => $this->t('Rename uploaded image by random numbers, to solve problems with non-ASCII filenames such as Chinese.'),
      '#required' => TRUE,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
    ];
    $form['basic']['num'] = [
      '#title' => $this->t('Number of albums'),
      '#weight' => 10,
      '#type' => 'details',
      '#description' => $this->t('The number of albums a user allowed to create. Administrater is not limited.'),
      '#tree' => TRUE,
    ];

    $roles = user_roles(TRUE);
    foreach ($roles as $key => $role) {
      $form['basic']['num']['photos_pnum_' . $key] = [
        '#type' => 'number',
        '#title' => $role->label(),
        '#required' => TRUE,
        '#default_value' => $config->get('photos_pnum_' . $key) ? $config->get('photos_pnum_' . $key) : 20,
        '#min' => 1,
        '#step' => 1,
        '#prefix' => '<div class="photos-admin-inline">',
        '#suffix' => '</div>',
        '#size' => 10,
      ];
    }
    // Thumb settings.
    if ($size = PhotosImage::sizeInfo()) {
      $num = ($size['count'] + 3);
      $sizes = [];
      foreach ($size['size'] as $style => $label) {
        $sizes[] = [
          'style' => $style,
          'label' => $label,
        ];
      }
      $size['size'] = $sizes;
    }
    else {
      // @todo remove else or use $size_options?
      $num = 3;
      $size['size'] = [
        [
          'style' => 'medium',
          'label' => 'Medium',
        ],
        [
          'style' => 'large',
          'label' => 'Large',
        ],
        [
          'style' => 'thumbnail',
          'label' => 'Thumbnail',
        ],
      ];
    }
    $form['photos_thumb_count'] = [
      '#type' => 'hidden',
      '#default_value' => $num,
    ];
    $form['thumb'] = [
      '#title' => $this->t('Image sizes'),
      '#weight' => -4,
      '#type' => 'details',
      '#description' => $this->t('Default image sizes. Note: if an image style is deleted after it has been in use for some
        time that may result in broken external image links.'),
    ];
    $thumb_options = image_style_options();
    if (empty($thumb_options)) {
      $image_style_link = Link::fromTextAndUrl($this->t('add image styles'), Url::fromRoute('entity.image_style.collection'))->toString();
      $form['thumb']['image_style'] = [
        '#markup' => '<p>One or more image styles required: ' . $image_style_link . '.</p>',
      ];
    }
    else {
      $form['thumb']['photos_pager_imagesize'] = [
        '#type' => 'select',
        '#title' => 'Pager size',
        '#default_value' => $config->get('photos_pager_imagesize'),
        '#description' => $this->t('Default pager block image style.'),
        '#options' => $thumb_options,
        '#required' => TRUE,
      ];
      $form['thumb']['photos_cover_imagesize'] = [
        '#type' => 'select',
        '#title' => 'Cover size',
        '#default_value' => $config->get('photos_cover_imagesize'),
        '#description' => $this->t('Default album cover image style.'),
        '#options' => $thumb_options,
        '#required' => TRUE,
      ];
      $form['thumb']['photos_name_0'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => isset($size['size'][0]['label']) ? $size['size'][0]['label'] : NULL,
        '#size' => '10',
        '#required' => TRUE,
        '#prefix' => '<div class="photos-admin-inline">',
      ];

      $form['thumb']['photos_size_0'] = [
        '#type' => 'select',
        '#title' => 'Thumb size',
        '#default_value' => isset($size['size'][0]['style']) ? $size['size'][0]['style'] : NULL,
        '#options' => $thumb_options,
        '#required' => TRUE,
        '#suffix' => '</div>',
      ];
      $empty_option = ['' => ''];
      $thumb_options = $empty_option + $thumb_options;
      $form['thumb']['additional_sizes'] = [
        '#markup' => '<p>Additional image sizes ' . Link::fromTextAndUrl($this->t('add more image styles'), Url::fromRoute('entity.image_style.collection'))->toString() . '.</p>',
      ];

      $additional_sizes = 0;
      for ($i = 1; $i < $num; $i++) {
        $form['thumb']['photos_name_' . $i] = [
          '#type' => 'textfield',
          '#title' => $this->t('Name'),
          '#default_value' => isset($size['size'][$i]['label']) ? $size['size'][$i]['label'] : NULL,
          '#size' => '10',
          '#prefix' => '<div class="photos-admin-inline">',
        ];
        $form['thumb']['photos_size_' . $i] = [
          '#type' => 'select',
          '#title' => $this->t('Size'),
          '#default_value' => isset($size['size'][$i]['style']) ? $size['size'][$i]['style'] : NULL,
          '#options' => $thumb_options,
          '#suffix' => '</div>',
        ];
        $additional_sizes = $i;
      }

      $form['thumb']['photos_additional_sizes'] = [
        '#type' => 'hidden',
        '#value' => $additional_sizes,
      ];
    }
    // End thumb settings.
    // Display settings.
    $form['display'] = [
      '#title' => $this->t('Display Settings'),
      '#type' => 'details',
    ];

    $form['display']['global'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Settings'),
      '#description' => $this->t('Albums basic display settings'),
    ];
    $form['display']['page'] = [
      '#type' => 'details',
      '#title' => $this->t('Page Settings'),
      '#description' => $this->t('Page (e.g: node/[nid]) display settings'),
      '#prefix' => '<div id="photos-form-page">',
      '#suffix' => '</div>',
    ];
    $form['display']['teaser'] = [
      '#type' => 'details',
      '#title' => $this->t('Teaser Settings'),
      '#description' => $this->t('Teaser display settings'),
      '#prefix' => '<div id="photos-form-teaser">',
      '#suffix' => '</div>',
    ];
    $form['display']['global']['photos_album_display_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Album display'),
      '#required' => TRUE,
      '#default_value' => $config->get('photos_album_display_type') ?: 'list',
      '#options' => [
        'list' => $this->t('List'),
        'grid' => $this->t('Grid'),
      ],
    ];
    $form['display']['global']['photos_display_viewpager'] = [
      '#type' => 'number',
      '#default_value' => $config->get('photos_display_viewpager'),
      '#title' => $this->t('How many images show in each page?'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
    ];
    $form['display']['global']['photos_album_column_count'] = [
      '#type' => 'number',
      '#default_value' => $config->get('photos_album_column_count') ?: 2,
      '#title' => $this->t('Number of columns'),
      '#description' => $this->t('When using album grid view.'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
    ];
    $form['display']['global']['photos_display_imageorder'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display order'),
      '#required' => TRUE,
      '#default_value' => $config->get('photos_display_imageorder'),
      '#options' => PhotosAlbum::orderLabels(),
    ];
    $list_imagesize = $config->get('photos_display_list_imagesize');
    $view_imagesize = $config->get('photos_display_view_imagesize');
    $size_options = PhotosImage::sizeOptions();
    $form['display']['global']['photos_display_list_imagesize'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display size (list)'),
      '#required' => TRUE,
      '#default_value' => $list_imagesize,
      '#description' => $this->t('Displayed in the list (e.g: photos/album/[nid]) of image size.'),
      '#options' => $size_options,
    ];
    $form['display']['global']['photos_display_view_imagesize'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display size (page)'),
      '#required' => TRUE,
      '#default_value' => $view_imagesize,
      '#description' => $this->t('Displayed in the page (e.g: photos/image/[fid]) of image size.'),
      '#options' => $size_options,
    ];
    $form['display']['global']['photos_display_user'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to modify this setting when they create a new album.'),
      '#default_value' => $config->get('photos_display_user') ?: 0,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
    ];
    if ($colorbox) {
      $form['display']['global']['photos_display_colorbox_max_height'] = [
        '#type' => 'number',
        '#default_value' => $config->get('photos_display_colorbox_max_height') ?: 100,
        '#title' => $this->t('Colorbox gallery maxHeight percentage.'),
        '#required' => TRUE,
        '#min' => 1,
        '#step' => 1,
      ];
      $form['display']['global']['photos_display_colorbox_max_width'] = [
        '#type' => 'number',
        '#default_value' => $config->get('photos_display_colorbox_max_width') ?: 50,
        '#title' => $this->t('Colorbox gallery maxWidth percentage.'),
        '#required' => TRUE,
        '#min' => 1,
        '#step' => 1,
      ];
    }
    $display_options = [
      $this->t('Do not display'),
      $this->t('Display cover'),
      $this->t('Display thumbnails'),
    ];
    if ($colorbox) {
      $display_options[3] = $this->t('Cover with colorbox gallery');
    }
    $form['display']['page']['photos_display_page_display'] = [
      '#type' => 'radios',
      '#default_value' => $config->get('photos_display_page_display'),
      '#title' => $this->t('Display setting'),
      '#required' => TRUE,
      '#options' => $display_options,
    ];
    $form['display']['page']['photos_display_full_viewnum'] = [
      '#type' => 'number',
      '#default_value' => $config->get('photos_display_full_viewnum'),
      '#title' => $this->t('Display quantity'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
      '#prefix' => '<div class="photos-form-count">',
    ];
    $form['display']['page']['photos_display_full_imagesize'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display size'),
      '#required' => TRUE,
      '#default_value' => $config->get('photos_display_full_imagesize'),
      '#options' => $size_options,
      '#suffix' => '</div>',
    ];
    $form['display']['page']['photos_display_page_user'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to modify this setting when they create a new album.'),
      '#default_value' => $config->get('photos_display_page_user') ?: 0,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
    ];
    $form['display']['teaser']['photos_display_teaser_display'] = [
      '#type' => 'radios',
      '#default_value' => $config->get('photos_display_teaser_display'),
      '#title' => $this->t('Display setting'),
      '#required' => TRUE,
      '#options' => $display_options,
    ];
    $form['display']['teaser']['photos_display_teaser_viewnum'] = [
      '#type' => 'number',
      '#default_value' => $config->get('photos_display_teaser_viewnum'),
      '#title' => $this->t('Display quantity'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
      '#prefix' => '<div class="photos-form-count">',
    ];
    $form['display']['teaser']['photos_display_teaser_imagesize'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display size'),
      '#required' => TRUE,
      '#default_value' => $config->get('photos_display_teaser_imagesize'),
      '#options' => $size_options,
      '#suffix' => '</div>',
    ];
    $form['display']['teaser']['photos_display_teaser_user'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to modify this setting when they create a new album.'),
      '#default_value' => $config->get('photos_display_teaser_user') ?: 0,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
    ];
    // Count settings.
    $form['count'] = [
      '#title' => $this->t('Statistics'),
      '#type' => 'details',
    ];
    $form['count']['photos_image_count'] = [
      '#type' => 'radios',
      '#title' => $this->t('Count image views'),
      '#default_value' => $config->get('photos_image_count') ?: 0,
      '#description' => $this->t('Increment a counter each time image is viewed.'),
      '#options' => [$this->t('Enabled'), $this->t('Disabled')],
    ];
    $form['count']['photos_user_count_cron'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image quantity statistics'),
      '#default_value' => $config->get('photos_user_count_cron') ?: 0,
      '#description' => $this->t('Users/Site images and albums quantity statistics.'),
      '#options' => [$this->t('Update count when cron runs (affect the count update).'), $this->t('Update count when image is uploaded (affect the upload speed).')],
    ];
    // End count settings.
    // Exif settings.
    $form['exif'] = [
      '#title' => $this->t('Exif Settings'),
      '#type' => 'details',
      '#description' => $this->t('These options require the php exif extension to be loaded.'),
    ];
    $form['exif']['photos_exif'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show exif information'),
      '#default_value' => $config->get('photos_exif') ?: 0,
      '#description' => $this->t('When the image is available automatically read and display exif information.'),
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
      '#disabled' => (extension_loaded('exif') ? FALSE : TRUE),
    ];
    $form['exif']['photos_exif_cache'] = [
      '#type' => 'radios',
      '#title' => $this->t('Cache exif information'),
      '#default_value' => $config->get('photos_exif_cache') ?: 0,
      '#description' => $this->t('Exif information cache can improve access speed.'),
      '#options' => [$this->t('Do not cache'), $this->t('To database')],
      '#disabled' => (extension_loaded('exif') ? FALSE : TRUE),
    ];
    // End exif settings.
    if ($module_photos_access_exists) {
      $form['privacy'] = [
        '#title' => $this->t('Privacy settings'),
        '#type' => 'details',
      ];
      $form['privacy']['photos_private_file_styles'] = [
        '#type' => 'radios',
        '#title' => $this->t('Delete private image styles'),
        '#default_value' => $config->get('photos_private_file_styles') ?: 'automatic',
        '#description' => $this->t('Automatically delete to save disk space. Never delete to improve load speed.'),
        '#options' => ['automatic' => $this->t('Automatically delete'), 'never' => $this->t('Never delete')],
        '#disabled' => ($module_photos_access_exists ? FALSE : TRUE),
      ];
    }

    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);
    // Build $photos_size array.
    $size = [];
    for ($i = 0; $i < $form_state->getValue('photos_thumb_count'); $i++) {
      if ($form_state->getValue('photos_size_' . $i)) {
        $size[$form_state->getValue('photos_size_' . $i)] = $form_state->getValue('photos_name_' . $i);
      }
    }
    $photos_size = $size;

    // Set number of albums per role.
    $num = $form_state->getValue('num');
    foreach ($num as $rnum => $rcount) {
      $this->config('photos.settings')->set($rnum, $rcount);
    }

    $this->config('photos.settings')
      ->set('photos_access_photos', $form_state->getValue('photos_access_photos'))
      ->set('photos_additional_sizes', $form_state->getValue('photos_additional_sizes'))
      ->set('photos_album_column_count', $form_state->getValue('photos_album_column_count'))
      ->set('photos_album_display_type', $form_state->getValue('photos_album_display_type'))
      ->set('photos_comment', $form_state->getValue('photos_comment'))
      ->set('photos_cover_imagesize', $form_state->getValue('photos_cover_imagesize'))
      ->set('photos_display_colorbox_max_height', $form_state->getValue('photos_display_colorbox_max_height'))
      ->set('photos_display_colorbox_max_width', $form_state->getValue('photos_display_colorbox_max_width'))
      ->set('photos_display_full_imagesize', $form_state->getValue('photos_display_full_imagesize'))
      ->set('photos_display_full_viewnum', $form_state->getValue('photos_display_full_viewnum'))
      ->set('photos_display_imageorder', $form_state->getValue('photos_display_imageorder'))
      ->set('photos_display_list_imagesize', $form_state->getValue('photos_display_list_imagesize'))
      ->set('photos_display_page_display', $form_state->getValue('photos_display_page_display'))
      ->set('photos_display_page_user', $form_state->getValue('photos_display_page_user'))
      ->set('photos_display_teaser_display', $form_state->getValue('photos_display_teaser_display'))
      ->set('photos_display_teaser_imagesize', $form_state->getValue('photos_display_teaser_imagesize'))
      ->set('photos_display_teaser_user', $form_state->getValue('photos_display_teaser_user'))
      ->set('photos_display_teaser_viewnum', $form_state->getValue('photos_display_teaser_viewnum'))
      ->set('photos_display_user', $form_state->getValue('photos_display_user'))
      ->set('photos_display_view_imagesize', $form_state->getValue('photos_display_view_imagesize'))
      ->set('photos_display_viewpager', $form_state->getValue('photos_display_viewpager'))
      ->set('photos_exif', $form_state->getValue('photos_exif'))
      ->set('photos_exif_cache', $form_state->getValue('photos_exif_cache'))
      ->set('photos_image_count', $form_state->getValue('photos_image_count'))
      ->set('photos_num', $form_state->getValue('photos_num'))
      ->set('photos_pager_imagesize', $form_state->getValue('photos_pager_imagesize'))
      ->set('photos_path', $form_state->getValue('photos_path'))
      ->set('photos_plupload_status', $form_state->getValue('photos_plupload_status'))
      ->set('photos_private_file_styles', $form_state->getValue('photos_private_file_styles'))
      ->set('photos_rname', $form_state->getValue('photos_rname'))
      ->set('photos_size', $photos_size)
      ->set('photos_size_max', $form_state->getValue('photos_size_max'))
      ->set('photos_upzip', $form_state->getValue('photos_upzip'))
      ->set('photos_user_count_cron', $form_state->getValue('photos_user_count_cron'))
      ->save();

    // Set warning if private file path is not set.
    if (!PrivateStream::basePath() && $form_state->getValue('photos_access_photos')) {
      drupal_set_message($this->t('Warning: image files can still be accessed by visiting the direct URL.
        For better security, ask your website admin to setup a private file path.'), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'photos.settings',
    ];
  }

}
