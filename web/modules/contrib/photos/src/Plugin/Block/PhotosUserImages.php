<?php

namespace Drupal\photos\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\photos\PhotosImage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Photos user images' block.
 *
 * @Block(
 *   id = "photos_user_images",
 *   admin_label = @Translation("User's images"),
 *   category = @Translation("Photos")
 * )
 */
class PhotosUserImages extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which view access should be checked.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account, Connection $connection, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->account = $account;
    $this->connection = $connection;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('database'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    // Check if user can view photos.
    if ($account->hasPermission('view photo')) {
      $access = AccessResult::allowed();
    }
    else {
      $access = AccessResult::forbidden();
    }
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve existing configuration for this block.
    // @todo migrate variables to block configuration.
    $config = $this->getConfiguration();
    $count = isset($config['image_count']) ? $config['image_count'] : 10;

    // Check current path for args to find uid.
    $current_path = trim($this->routeMatch->getRouteObject()->getPath(), '/');
    $arg = explode('/', $current_path);
    if (isset($arg[1])) {
      if ($arg[0] == 'photos' && isset($arg[2])) {
        switch ($arg[1]) {
          case 'image':
            $fid = $this->routeMatch->getRawParameter('file');
            $uid = $this->connection->query('SELECT uid FROM {file_managed} WHERE fid = :fid',
              [':fid' => $fid])->fetchField();
            break;

          case 'user':
            $uid = $this->routeMatch->getRawParameter('user');
        }
      }
      if ($arg[0] == 'node' && isset($arg[1])) {
        $nid = $this->routeMatch->getRawParameter('node');
        $uid = $this->connection->query('SELECT uid FROM {node_field_data} WHERE nid = :nid',
          [':nid' => $nid])->fetchField();
      }
    }
    if (!isset($uid)) {
      $uid = $this->account->id();
    }
    if ($uid && ($block_info = PhotosImage::blockView('user', $count, 'photos/image', $uid))) {
      return [
        '#markup' => $block_info['content'],
        '#title' => $block_info['title'],
        '#cache' => [
          'tags' => [
            'photos:image:user:' . $uid,
            'user:' . $uid,
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    // Add a form field to the existing block configuration form.
    $options = array_combine(
      [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30, 40],
      [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30, 40]
    );
    $form['image_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of images to display'),
      '#options' => $options,
      '#default_value' => isset($config['image_count']) ? $config['image_count'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('image_count', $form_state->getValue('image_count'));
  }

}
