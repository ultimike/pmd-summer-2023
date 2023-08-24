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
   * Test callback.
   */
  public function testSomething(): void {
    $admin_user = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin');
    $this->assertSession()->elementExists('xpath', '//h1[text() = "Administration"]');
  }

}
