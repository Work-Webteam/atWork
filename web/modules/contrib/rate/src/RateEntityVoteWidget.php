<?php

namespace Drupal\rate;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\votingapi\VoteResultFunctionManager;

/**
 * The rate.entity.vote_widget service.
 */
class RateEntityVoteWidget {

  /**
   * The config factory wrapper to fetch settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Account proxy (the current user).
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * Votingapi result manager.
   *
   * @var \Drupal\votingapi\VoteResultFunctionManager
   */
  protected $resultManager;

  /**
   * Constructs a RateEntityVoteWidget object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The account proxy.
   * @param \Drupal\votingapi\VoteResultFunctionManager $result_manager
   *   The vote result manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              EntityTypeManagerInterface $entity_type_manager,
                              AccountProxyInterface $account_proxy,
                              VoteResultFunctionManager $result_manager) {
    $this->config = $config_factory->get('rate.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->accountProxy = $account_proxy;
    $this->resultManager = $result_manager;
  }

  /**
   * Returns a renderable array of the updated vote totals.
   *
   * @param string $entity_id
   *   The entity id.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   * @param string|null $widget_type
   *   (optional) The widget type. If NULL, it will use value from config.
   *
   * @return array
   *   A renderable array.
   */
  public function buildRateVotingWidget($entity_id, $entity_type_id, $bundle, $widget_type = NULL) {
    $output = [];
    $enabled_types_widgets = $this->config->get('enabled_types_widgets');

    if (isset($enabled_types_widgets[$entity_type_id]) && in_array($bundle, array_keys($enabled_types_widgets[$entity_type_id]))) {
      if (empty($widget_type)) {
        $widget_type = $enabled_types_widgets[$entity_type_id][$bundle]['widget_type'];
      }
      $rate_theme = 'rate_template_' . $widget_type;
      $use_ajax = $this->config->get('use_ajax');
      /** @var \Drupal\votingapi\VoteStorageInterface $vote_storage */
      $vote_storage = $this->entityTypeManager->getStorage('vote');
      $vote_ids = $vote_storage->getUserVotes(
        $this->accountProxy->id(),
        NULL,
        $entity_type_id,
        $entity_id
      );
      $has_voted = (!empty($vote_ids)) ? TRUE : FALSE;
      $user_can_vote = $this->accountProxy->hasPermission('cast rate vote on ' . $entity_type_id . ' of ' . $bundle);

      // Set the theme variables.
      $output['rate_vote_widget'] = [
        '#theme' => $rate_theme,
        '#results' => $this->resultManager->getResults($entity_type_id, $entity_id),
        '#use_ajax' => $use_ajax,
        '#can_vote' => $user_can_vote,
        '#has_voted' => $has_voted,
        '#entity_id' => $entity_id,
        '#entity_type_id' => $entity_type_id,
        '#attributes' => ['class' => ['links', 'inline']],
        '#widget_type' => $widget_type,
        '#cache' => [
          'contexts' => ['user'],
          'tags' => ['vote:' . $bundle . ':' . $entity_id],
        ],
      ];
    }

    return $output;
  }

  /**
   * Rate widgets.
   *
   * @return array
   *   An array whose keys are widget types and whose values are widget names.
   */
  public static function getRateWidgets() {
    return [
      "fivestar" => "Fivestar",
      "number_up_down" => "Number Up / Down",
      "thumbs_up" => "Thumbs Up",
      "thumbs_up_down" => "Thumbs Up / Down",
      "yesno" => "Yes / No",
    ];
  }

}
