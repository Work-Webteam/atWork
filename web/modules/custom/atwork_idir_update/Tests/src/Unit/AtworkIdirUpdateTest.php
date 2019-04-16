<?php

namespace Drupal\Tests\atwork_idir_update;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\atwork_idir_update\AtworkIdirUpdateInterface;
use Drupal\atwork_idir_update\TestAtworkIdirUpdate;
use Drupal\atwork_idir_update\AtworkIdirLog;
/**
 * @file
 * @group atwork_idir_update
 */
class FileGrabTest extends EntityKernelTestBase  {
  public function testRetrieveFile(){
    $new_update = new TestAtworkIdirUpdate();
    $new_update->splitFile();
    //print_r($new_update);
  }

  public function testGUIDCheck() {
    $guid = 'test';
    $new_update = new TestAtworkIdirUpdate();
    $check = $new_update->getGUIDField($guid);
    print_r($check);
  }

  public function testErrorLog() {
    $test_class = new TestAtworkIdirUpdate();
    call_user_func(AtworkIdirLog::errorCollect("This is an error"));
    call_user_func(AtworkIdirLog::success("This is a log"));
    call_user_func(AtworkIdirLog::notify());
  }

}
