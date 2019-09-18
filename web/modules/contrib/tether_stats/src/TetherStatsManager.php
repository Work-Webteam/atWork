<?php

namespace Drupal\tether_stats;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Url;
use Drupal\tether_stats\TetherStatsIdentitySetInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Template\Attribute;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * The Tether Stats manager service.
 *
 * Provides access to the database storage object.
 */
class TetherStatsManager implements TetherStatsManagerInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  private $classResolver;

  /**
   * The database storage object for activity tracking.
   *
   * @var TetherStatsStorageInterface
   */
  private $storage;

  /**
   * The database storage object for data mining and analytics.
   *
   * @var TetherStatsAnalyticsStorageInterface
   */
  private $analyticsStorage;

  /**
   * The stats element object representing the current page request.
   *
   * @var TetherStatsElementInterface
   */
  private $element;

  /**
   * The Tether Stats configuration settings.
   *
   * @var ImmutableConfig
   */
  private $settings;

  /**
   * The error logger for the 'tether_stats' channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  private $linkGenerator;

  /**
   * The link generator service.
   *
   * @var TetherStatsChartRendererPluginManager
   */
  private $chartPluginManager;

  /**
   * Constructs the TetherStatsManager service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The error logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\tether_stats\TetherStatsChartRendererPluginManager $chart_plugin_manager
   *   The chart renderer plugin manager.
   */
  public function __construct(Connection $database, LoggerInterface $logger, ConfigFactoryInterface $config_factory, LinkGeneratorInterface $link_generator, TetherStatsChartRendererPluginManager $chart_plugin_manager) {

    $this->database = $database;
    $this->settings = $config_factory->get('tether_stats.settings');
    $this->logger = $logger;
    $this->linkGenerator = $link_generator;
    $this->chartPluginManager = $chart_plugin_manager;

    // If an alternative database is supplied in the settings, then override the
    // default database connection.
    $settings_database = $this->settings->get('database');

    if ($settings_database && $settings_database != 'default') {

      $this->database = Database::getConnection('default', $settings_database);
    }

    $this->storage = new TetherStatsStorage($this->database);
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {

    return $this->storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnalyticsStorage() {

    if (!isset($this->analyticsStorage)) {

      $this->analyticsStorage = new TetherStatsAnalyticsStorage($this->database);
    }

    return $this->analyticsStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {

    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogger() {

    return $this->logger;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {

    return $this->settings->get('active');
  }

  /**
   * {@inheritdoc}
   */
  public function getElement() {

    return $this->element;
  }

  /**
   * {@inheritdoc}
   */
  public function hasElement() {

    return isset($this->element);
  }

  /**
   * {@inheritdoc}
   */
  public function getChartRenderer() {
    $renderer =& drupal_static(__FUNCTION__);

    if (!isset($renderer)) {

      $plugin_id = $this->settings->get('chart_plugin');

      if (!$this->chartPluginManager->hasDefinition($plugin_id)) {

        // Fallback to Google Charts if the plugin does not exist. It
        // may have been removed.
        $plugin_id = 'tether_stats_google_charts';
      }

      $renderer = $this->chartPluginManager->createInstance($plugin_id);
    }
    return $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function setElement(TetherStatsElementInterface $element) {

    $this->element = $element;
  }

  /**
   * {@inheritdoc}
   */
  public function generateLink($text, Url $url, TetherStatsIdentitySetInterface $identity_set) {

    $options_attributes = $url->getOption('attributes');

    if (!isset($options_attributes)) {

      $options_attributes = [];
    }

    $attributes = new Attribute($options_attributes);

    if (!$attributes->hasClass('tether_stats-track-link')) {

      $attributes->addClass('tether_stats-track-link');
    }

    $options_attributes['class'] = $attributes->storage()['class']->value();

    foreach ($identity_set->getIdentityParams() as $key => $value) {

      $options_attributes["data-{$key}"] = $value;
    }

    $url->setOption('attributes', $options_attributes);

    return $this->linkGenerator->generate($text, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function testValidityOfIdentitySet(TetherStatsIdentitySetInterface $identity_set) {

    $is_valid = FALSE;

    try {

      // Validate the identity set and log any errors.
      $is_valid = $identity_set->isValid();
    }
    catch (\InvalidArgumentException $e) {

      $this->logger->error($e->getMessage());
    }
    catch (\Drupal\tether_stats\Exception\TetherStatsDerivativeNotFoundException $e) {

      $derivative = SafeMarkup::checkPlain($identity_set->get('derivative'));

      $this->logger->error("An identity set referenced a derivative named '{$derivative}' that does not exist.");
    }
    catch (\Drupal\tether_stats\Exception\TetherStatsDerivativeDisabledException $e) {

      $derivative = SafeMarkup::checkPlain($identity_set->get('derivative'));

      $this->logger->warning("A disabled derivative '{$derivative}' was applied to an identity set. No element was created.");
    }
    catch (\Drupal\tether_stats\Exception\TetherStatsIncompleteIdentitySetException $e) {

      $keys = array_keys($identity_set->all());
      $parameters_provided = SafeMarkup::checkPlain(implode(', ', $keys));

      $this->logger->error("An identity set did not have sufficient parameters to uniquely define an element. Parameters provided: {$parameters_provided}.");
    }
    catch (\Drupal\tether_stats\Exception\TetherStatsDerivativeInvalidException $e) {

      $derivative = SafeMarkup::checkPlain($identity_set->get('derivative'));

      $this->logger->error("The derivative '{$derivative}' was applied to an identity set that violated its constraints.");
    }
    catch (\Drupal\tether_stats\Exception\TetherStatsEntityInvalidException $e) {

      $entity_type = SafeMarkup::checkPlain($identity_set->get('entity_type'));
      $entity_id = SafeMarkup::checkPlain($identity_set->get('entity_id'));

      $this->logger->error("The entity referenced in an identity set was invalid. Entity type: '{$entity_type}'. Entity Id: {$entity_id}.");
    }

    return $is_valid;
  }

}
