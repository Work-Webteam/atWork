<?php
namespace Drupal\atwork_idir_update;
use Drupal\Database\Core\Database\Database;
use Drupal\user\Entity\User;

class AtworkIdirUpdate
{
  protected $timestamp;
  protected $drupal_path;
  
  function __construct()
  {
    // Use timestamp and drupal_path mainly for files (accessing/writing etc) - so setting them here once.
    $this->timestamp = date('Ymd');
    // TODO: Should these be going into the Public:// file folder?
    $this->drupal_path = $this->getModulePath('atwork_idir_update');
  }

  protected function getModulePath($moduleName)
  {
    return drupal_get_path('module', $moduleName);
  }
}