<?php

/**
 * Contains \Drupal\siteminder\Controller\SiteminderController.
 *
 * @file
 */

namespace Drupal\siteminder\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\siteminder\Service\Siteminder;
use Drupal\siteminder\Service\SiteminderDrupalAuthentication;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for register/login Siteminder variables.
 */
class SiteminderController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The Siteminder Authentication helper service.
   *
   * @var \Drupal\siteminder\Service\Siteminder
   */
  public $siteminder;

  /**
   * The Siteminder Drupal Authentication service.
   *
   * @var \Drupal\siteminder\Service\SiteminderDrupalAuthentication
   */
  public $siteminderDrupalauth;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   * @param Siteminder $siteminder
   *   The Siteminder Authentication helper service.
   * @param SiteminderDrupalAuthentication $siteminder_drupalauth
   *   The Siteminder Drupal Authentication service.
   * @param AccountInterface $account
   *   The current account.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(Siteminder $siteminder, SiteminderDrupalAuthentication $siteminder_drupalauth, AccountInterface $account, ConfigFactoryInterface $config_factory) {
    $this->siteminder = $siteminder;
    $this->siteminderDrupalauth = $siteminder_drupalauth;
    $this->account = $account;
    $this->config = $config_factory->get('siteminder.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
            $container->get('siteminder.siteminderhelper'), $container->get('siteminder.drupalauthentication'), $container->get('current_user'), $container->get('config.factory')
    );
  }

  /**
   * Logs the user in via Siteminder.
   *
   * @return RedirectResponse
   *   A redirection to application Homepage.
   */
  public function authenticate() {

    // Checking if all the SM HTTP header variables are present
    If ($this->siteminder->manageErrorCases()) {
      return $this->redirect('siteminder.access_denied');
    }

    // Get the GUID from the HTTP header
    $id = $this->siteminder->getId();

    // Check if the GUID matches an UID database record
    $uid = $this->siteminder->getUid($id);

    if ($uid) {
      // User logged into Siteminder Agent and we got the unique identifier, so try to log into Drupal.
      // Check to see whether the external user exists in Drupal. If they do not exist, set a message and an error 404 page.
      $account = $this->siteminderDrupalauth->externalLoginRegister($id);
    }
    // The ID is not yet in the database
    // Create a new user.
    else {
      $account = $this->siteminderDrupalauth->externalRegister($id);
      // If the option has been enabled, the user is redirected to a form to manually enter information
      if ($this->config->get('user.username_form')) {
        $route_parameters = [
          'user' => \Drupal::currentUser()->id(),
          'status' => 'new_user'
        ];
        return $this->redirect('entity.user.edit_form', $route_parameters);
      }
    }

    // If connected as a Drupal user via Siteminder, go to the homepage with a success message
    if ($account) {
      // The user account is waiting for validation
      if (!$account->isActive()) {
        return $this->redirect('siteminder.pending_validation');
      } else {
        drupal_set_message('Connected as ' . $account->getDisplayName(), 'status');
        return $this->redirect('<front>');
      }
    }
    // Else, the user is redirected to the access denied page with the error messages
    else {
      return $this->redirect('siteminder.access_denied');
    }
  }

  /**
   * Access denied page
   *
   * @return array
   *   A render array containing the message to display for error pages.
   */
  public function denied() {
    return [
      '#markup' => 'Access denied - Please contact web services to resolve this issue',
    ];
  }

  /**
   * Pending validation page
   *
   * @return array
   *   A render array containing the message to display for pending validation user.
   */
  public function pending() {
    drupal_set_message('Thank you for confirming your user information. Your account will be disabled until an Administrator approves it.', 'warning');
    return [
      '#markup' => $this->config->get('user.pending_message'),
    ];
  }

}
