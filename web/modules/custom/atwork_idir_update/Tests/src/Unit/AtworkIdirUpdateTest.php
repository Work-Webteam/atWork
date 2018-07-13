<?php

namespace Drupal\Tests\atwork_idir_update;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\atwork_idir_update\AtworkIdirUpdateInterface;
use Drupal\atwork_idir_update\TestAtworkIdirUpdate;
/**
 * @file
 * @group atwork_idir_update
 */

class FileGrabTest extends EntityKernelTestBase  {
  public function testRetrieveFile(){
    $new_update = new TestAtworkIdirUpdate();
    $new_update->splitFile();
    print_r($new_update);
  } 
}