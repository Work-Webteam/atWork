<?php
/**
 * @file
 * @group atwork_idir_update
 */

namespace Drupal\Tests\atwork_idir_update;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\atwork_idir_update\AtworkIdirUpdateUserInterface;
use Drupal\atwork_idir_update\AtworkIdirUpdateUser;

/**
 * @coversDefaultClass \Drupal\atwork_idir_update\AtworkIdirUpdateUser
 * @group atwork_idir_update
 */
class UserCreationTest extends EntityKernelTestBase
{
    public function testEmpty()
    {
      $user_array = [
        'name' => 'Employee News',
        'guid' => 'test'
      ];
      $testuser = new AtworkIdirUpdateUser('system', $user_array);
    }
}

