<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;

/**
 * Main functional test for the entire repository metadata process.
 *
 * @group drupaleasy_repositories
 */
final class AddYmlRepoTest extends BrowserTestBase {
  use RepositoryContentTypeTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable the Yml Remote plugin.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories_plugins', ['yml_remote' => 'yml_remote']);
    $config->save();

    // Create and login as a Drupal user with permission to access the
    // DrupalEasy Repositories Settings page. This is UID=2 because UID=1 is
    // created by
    // web/core/lib/Drupal/Core/Test/FunctionalTestSetupTrait::installParameters().
    // This root user can be accessed via $this->rootUser.
    $admin_user = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($admin_user);

    // $this->createRepositoryContentType();
    // Ensure that the new Repository URL field is visible in the existing
    // user entity form mode.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('user', 'user')
      ->setComponent('field_repository_url', [
        'type' => 'link_default',
      ])
      ->save();
  }

  /**
   * Test that the settings page can be reached and works as expected.
   *
   * This tests that an admin user can access the settings page, select a plugin
   * to enable, and submit the page successfully.
   *
   * @return void
   *   Returns nothing.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSettingsPage(): void {
    // Get a handle on the browsing session.
    $session = $this->assertSession();

    // Navigate to the DrupalEasy Repositories Settings page and confirm we can
    // reach it.
    $this->drupalGet('/admin/config/services/repositories');
    // Test to ensure that the page loads without error.
    $session->statusCodeEquals(200);

    // Check the "Yml remote" checkbox.
    $edit = [
      'edit-repositories-plugins-yml-remote' => 'yml_remote',
    ];

    // Submit the form.
    $this->submitForm($edit, 'Save configuration', 'drupaleasy-repositories-settings');

    // Basic checks.
    $session->statusCodeEquals(200);
    $session->responseContains('The configuration options have been saved.');

    // Ensure the "Yml remote" checkbox is still checked.
    $session->checkboxChecked('edit-repositories-plugins-yml-remote');
    $session->checkboxNotChecked('edit-repositories-plugins-github');
  }

  /**
   * Test that the settings page cannot be reached without permission.
   *
   * @return void
   *   Returns nothing.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUnprivilegedSettingsPage(): void {
    $session = $this->assertSession();
    $authenticated_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($authenticated_user);
    $this->drupalGet('/admin/config/services/repositories');
    // Test to ensure that the page loads without error.
    // See https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
    $session->statusCodeEquals(403);
  }

  /**
   * Test that a yml repo can be added to a profile by a user.
   *
   * This tests that a yml-based repo can be added to a user's profile and that
   * a repository node is successfully created upon saving the profile.
   *
   * @return void
   *   Returns nothing.
   *
   * @test
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAddYmlRepo(): void {
    // Create an login as a Drupal user with permission to access content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // Get a handle on the browsing session.
    $session = $this->assertSession();

    // Go to the user profile page for this user.
    $this->drupalGet('/user/' . $user->id() . '/edit');
    $session->statusCodeEquals(200);

    // Figure out the full path to the test .yml file.
    /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
    $module_handler = \Drupal::service('module_handler');
    $module = $module_handler->getModule('drupaleasy_repositories');
    $module_full_path = \Drupal::request()->getUri() . $module->getPath();

    // Add the test .yml file path to the user profile form and submit.
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
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository');
    $query->accessCheck(TRUE);
    $results = $query->execute();
    $session->assert(count($results) === 1, 'Either 0 or more than 1 repository nodes were found.');
  }

}
