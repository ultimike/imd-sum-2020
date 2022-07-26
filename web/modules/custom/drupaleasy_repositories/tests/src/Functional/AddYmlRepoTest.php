<?php

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
class AddYmlRepoTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'node',
    'link',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories', ['yml_remote' => 'yml_remote']);
    $config->save();

    // Create and login as a Drupal admin user with permission to access
    // the DrupalEasy Repositories Settings page. This is UID=1.
    $admin_user = $this->drupalCreateUser(['drupaleasy repositories configure']);
    $this->drupalLogin($admin_user);

  }

  /**
   * Test callback.
   */
  public function testSomething() {
    $admin_user = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin');
    $this->assertSession()->elementExists('xpath', '//h1[text() = "Administration"]');
  }

}
