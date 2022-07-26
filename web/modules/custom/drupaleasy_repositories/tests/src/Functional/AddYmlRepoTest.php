<?php

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;

//use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;
//use Drupal\field\Entity\FieldStorageConfig;
//use Drupal\field\Entity\FieldConfig;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
class AddYmlRepoTest extends BrowserTestBase {
  //use RepositoryContentTypeTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['drupaleasy_repositories'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories', ['yml_remote' => 'yml_remote']);
    $config->save();

    // Create and login as a Drupal admin user with permission to access
    // the DrupalEasy Repositories Settings page. This is UID=2 because UID=1
    // is created by FunctionalTestSetupTrait. The root user can be accessed
    // with $this->rootUser.
    $admin_user = $this->drupalCreateUser(['drupaleasy repositories configure']);
    $this->drupalLogin($admin_user);

//    $this->createRepositoryContentType();
//
//    FieldStorageConfig::create([
//      'field_name' => 'field_repository_url',
//      'type' => 'link',
//      'entity_type' => 'user',
//      'cardinality' => -1,
//    ])->save();
//    FieldConfig::create([
//      'field_name' => 'field_repository_url',
//      'entity_type' => 'user',
//      'bundle' => 'user',
//      'label' => 'Repository URL',
//    ])->save();
//
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository  */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('user', 'user', 'default')
      ->setComponent('field_repository_url', ['type' => 'link_default'])
      ->save();

  }

  /**
   * Test callback.
   */
//  public function testSomething():void {
//    $another_user = $this->drupalCreateUser(['access administration pages']);
//    $this->drupalLogin($another_user);
//    $this->drupalGet('admin');
//    $this->assertSession()->elementExists('xpath', '//h1[text() = "Administration"]');
//  }

  /**
   * Test that the settings page can be reached and works as expected.
   *
   * This tests that an admin user can access the settings page, select a
   * plugin to enable, and submit the page successfully.
   *
   * @test
   */
  public function testSettingsPage(): void {
    // Start the browsing session.
    $session = $this->assertSession();

    // Navigate to the DrupalEasy Repositories Settings page and confirm we
    // can reach it.
    $this->drupalGet('/admin/config/services/repositories');
    // Try this with a 500 status code to see it fail.
    $session->statusCodeEquals(200);
  }

}
