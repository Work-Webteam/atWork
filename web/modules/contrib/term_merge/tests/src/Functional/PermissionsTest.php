<?php

namespace Drupal\Tests\term_merge\Functional;

/**
 * Tests the Term Merge module permissions.
 *
 * @group term_merge
 */
class PermissionsTest extends TermMergeTestBase {

  /**
   * Data provider for the testPermissions test.
   *
   * @return array
   *   The test data sets containing:
   *   permissions:        string[]
   *      Contains the permissions the user should have.
   *   expectedStatusCode: int
   *      The status code that should be returned.
   */
  public function permissionsProvider() {
    $testData = [];

    $testData['no permissions'] = [
      'permissions' => [],
      'expectedStatusCode' => 403,
    ];

    $testData['no edit permission'] = [
      'permissions' => ['merge taxonomy terms'],
      'expectedStatusCode' => 403,
    ];

    $testData['edit permission'] = [
      'permissions' => [
        'merge taxonomy terms',
        'edit terms in %vocabulary_id',
      ],
      'expectedStatusCode' => 200,
    ];

    return $testData;
  }

  /**
   * Tests that users without the merge taxonomy terms permission can't merge.
   *
   * @param array $permissions
   *   The permissions the test user should have.
   * @param int $expectedStatusCode
   *   The status code that should be in the response.
   *
   * @dataProvider permissionsProvider
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPermissions(array $permissions, $expectedStatusCode) {
    // Data providers run before the setUp. This causes fatal errors when
    // running the test. We therefore have to do a replacement.
    foreach ($permissions as $key => $permission) {
      $permissions[$key] = str_replace('%vocabulary_id', $this->vocabulary->id(), $permission);
    }

    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    $this->drupalGet("/admin/structure/taxonomy/manage/{$this->vocabulary->id()}/merge");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

}
