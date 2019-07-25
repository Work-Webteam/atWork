<?php

namespace Drupal\atwork_idir_update;

use Drupal\atwork_idir_update\AtworkIdirUpdate;

/**
 * Class TestAtworkIdirUpdate.
 *
 * Test class for module - not currently implemented.
 *
 * @package Drupal\atwork_idir_update
 */
class TestAtworkIdirUpdate extends AtworkIdirUpdate {

  /**
   * Test function for path.
   *
   * Override parent::getModulePath()
   * Do NOT call parent:getModulePath inside this function
   * or you will receive the original error
   * For testing purposes only.
   *
   * @param string $moduleName
   *   The name of the module we wish to find.
   *
   * @return string
   *   The path to the module as requested.
   */
  protected function getModulePath($moduleName) {
    return 'modules/custom/atwork_idir_update';
  }

}
