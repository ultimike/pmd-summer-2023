<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class DrupaleasyRepositoriesPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['drupaleasy_repositories'];

  /**
   * Our plugin manager.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected DrupaleasyRepositoriesPluginManager $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.drupaleasy_repositories');
  }

  /**
   * Test callback.
   */
  public function testSomething(): void {
    self::assertTrue(TRUE);
  }

}
