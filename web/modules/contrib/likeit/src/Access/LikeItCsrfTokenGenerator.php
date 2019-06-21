<?php

namespace Drupal\likeit\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;

/**
 * Generates and validates CSRF tokens.
 *
 * We have to use it because Drupal\Core\Access\CsrfTokenGenerator does not
 * work with Anonymous user.
 */
class LikeItCsrfTokenGenerator {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * Read only site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The token seed.
   *
   * @var string
   */
  protected $tokenSeed = 's';

  /**
   * Constructs the token generator.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Site\Settings $settings
   *   Read only site settings.
   */
  public function __construct(PrivateKey $private_key, ConfigFactoryInterface $config_factory, Settings $settings) {
    $token_seed = $config_factory->get('likeit.settings')->get('token_seed');
    $this->privateKey = $private_key;
    $this->settings = $settings;

    if ($token_seed) {
      $this->tokenSeed = $token_seed;
    }

  }

  /**
   * Generates a token based on $value, the token seed, and the private key.
   *
   * @param string $value
   *   (optional) An additional value to base the token on.
   *
   * @return string
   *   A 43-character URL-safe token for validation.
   */
  public function get($value = '') {
    $seed = $this->tokenSeed;

    return $this->computeToken($seed, $value);
  }

  /**
   * Validates a token based on $value, the token seed, and the private key.
   *
   * @param string $token
   *   The token to be validated.
   * @param string $value
   *   (optional) An additional value to base the token on.
   *
   * @return bool
   *   TRUE for a valid token, FALSE for an invalid token.
   */
  public function validate($token, $value = '') {
    $seed = $this->tokenSeed;
    if (empty($seed)) {
      return FALSE;
    }

    return Crypt::hashEquals($this->computeToken($seed, $value), $token);
  }

  /**
   * Generates a token based on $value, the token seed, and the private key.
   *
   * @param string $seed
   *   The token seed.
   * @param string $value
   *   (optional) An additional value to base the token on.
   *
   * @return string
   *   A 43-character URL-safe token for validation.
   */
  protected function computeToken($seed, $value = '') {
    return Crypt::hmacBase64($value, $seed . $this->privateKey->get() . $this->settings->getHashSalt());
  }

}
