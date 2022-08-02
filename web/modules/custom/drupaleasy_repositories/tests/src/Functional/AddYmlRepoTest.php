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
//  public function testSomething(): void {
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

    // Select the "Remote .yml file" checkbox and submit the form.
    $edit = [
      'edit-repositories-yml-remote' => 'yml_remote',
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->responseNotContains('The value is not correct.');
    $session->responseContains('The configuration options have been saved.');
    $session->checkboxChecked('edit-repositories-yml-remote');
    $session->checkboxNotChecked('edit-repositories-github');

  }

  /**
   * Test that a yml repo can be added to profile by a user.
   *
   * This tests that a yml-based repo can be added to a user's profile and
   * that a repository node is successfully created upon saving the profile.
   *
   * @test
   */
  public function testAddYmlRepo(): void {
    // Create and login as a Drupal user with permission to access
    // content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // Start the browsing session.
    $session = $this->assertSession();

    // Navigate to their edit profile page and confirm we can reach it.
    $this->drupalGet('/user/' . $user->id() . '/edit');
    // Try this with a 500 status code to see it fail.
    $session->statusCodeEquals(200);

    // Get the full path to the test .yml file.
    /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
    $module_handler = \Drupal::service('module_handler');
    /** @var \Drupal\Core\Extension\Extension $module */
    $module = $module_handler->getModule('drupaleasy_repositories');
    $module_full_path = \Drupal::request()->getUri() . $module->getPath();

    // Add the test .yml file path and submit the form.
    $edit = [
      'field_repository_url[0][uri]' => $module_full_path . '/tests/assets/batman-repo.yml',
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    $session->responseContains('The changes have been saved.');
    // We can't check for the following message unless we also have the future
    // drupaleasy_notify module enabled.
    // $session->responseContains('The repo named <em class="placeholder">The Batman repository</em> has been created');

    // Find the new repository node.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository')->accessCheck(FALSE);
    $results = $query->execute();
    $session->assert(count($results) === 1, 'One repository node was found.');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $node_storage = $entity_type_manager->getStorage('node');
    /** @var \Drupal\node\Entity\Node $node */
    $node = $node_storage->load(reset($results));

    // Check values.
    $session->assert($node->field_machine_name->value == 'batman-repo', 'Machine name did not match.');
    $session->assert($node->field_source->value == 'yml_remote', 'Source did not match.');
    $session->assert($node->title->value == 'The Batman repository', 'Label did not match.');
    $session->assert($node->field_description->value == 'This is where Batman keeps all his crime-fighting code.', 'Description did not match.');
    $session->assert($node->field_number_of_issues->value == '6', 'Number of issues did not match.');

  }

}
