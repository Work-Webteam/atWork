<?php

namespace Drupal\atwork_idir_update;

use Drupal\atwork_idir_update\AtworkIdirUpdate;

class TestAtworkIdirUpdate extends AtworkIdirUpdate 
{
  /**
   * Override parent::getModulePath()
   * Do NOT call parent:LgetModulePath inside this function
   * or you will receive the original error 
   * For testing purposes only.
   */
  protected function getModulePath($moduleName) {
    return 'modules/custom/atwork_idir_update';
  }
}