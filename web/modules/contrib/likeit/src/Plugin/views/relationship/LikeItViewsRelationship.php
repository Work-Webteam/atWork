<?php

namespace Drupal\likeit\Plugin\views\relationship;

use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\user\RoleInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a views relationship to a user.
 *
 * @ViewsRelationship("likeit_views_relationship")
 */
class LikeItViewsRelationship extends RelationshipPluginBase {

  /**
   * The kill switch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructs a LikeItViewsRelationship object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   The kill switch service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, KillSwitch $kill_switch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->killSwitch = $kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['likeit'] = ['default' => NULL];
    $options['required'] = ['default' => 1];
    $options['user_scope'] = ['default' => 'current'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {

    if ($this->options['user_scope'] == 'current') {
      $this->definition['extra'][] = [
        'field' => 'user_id',
        'value' => '***CURRENT_USER***',
        'numeric' => TRUE,
      ];

      $roles = user_roles(FALSE, 'likeit_like');
      if (isset($roles[RoleInterface::ANONYMOUS_ID])) {

        // Disable page caching for anonymous users.
        $this->killSwitch->trigger();

        // Add in the SID from Session API for anonymous users.
        $this->definition['extra'][] = [
          'field' => 'session_id',
          'value' => '***CURRENT_SESSION_ID***',
          'numeric' => TRUE,
        ];
      }
    }

    parent::query();
  }

}
