<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class DrupaleasyRepositoriesServiceTest extends KernelTestBase {
  use RepositoryContentTypeTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'node',
    'system',
    'user',
    'field',
    'text',
    'link',
  ];

  /**
   * The DrupalEasy Repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected DrupaleasyRepositoriesService $drupaleasyRepositoriesService;

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupaleasyRepositoriesService = $this->container->get('drupaleasy_repositories.service');
    $this->createRepositoryContentType();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $aquaman_repo = $this->getTestRepo('aquaman');
    $repo = reset($aquaman_repo);

    $this->adminUser = User::create([
      'name' => $this->randomString(),
    ]);
    $this->adminUser->save();

    $node = Node::create([
      'type' => 'repository',
      'title' => $repo['label'],
      'field_machine_name' => array_key_first($aquaman_repo),
      'field_url' => $repo['url'],
      'field_hash' => 'c8f3fd6cd928e6a1e62239a7fea461e7',
      'field_number_of_issues' => $repo['num_open_issues'],
      'field_source' => $repo['source'],
      'field_description' => $repo['description'],
      'user_id' => $this->adminUser->id(),
    ]);
    $node->save();

    // Enable the Yml Remote plugin.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories_plugins', ['yml_remote' => 'yml_remote']);
    $config->save();
  }

  /**
   * Data provider for testIsUnique.
   *
   * @return array<int, array<boolean|array<string, array<string, string|int>>>>
   *   Expected result and tests repository array.
   */
  public function providerTestIsUnique(): array {
    return [
      [FALSE, $this->getTestRepo('aquaman')],
      [TRUE, $this->getTestRepo('superman')],
    ];
  }

  /**
   * Tests our service's isUnique method.
   *
   * @param bool $expected
   *   The expected result.
   * @param array<string, array<string, string|int>> $repo
   *   The repository metadata to test.
   *
   * @covers \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService::isUnique
   * @test
   * @dataProvider providerTestIsUnique
   */
  public function testIsUnique(bool $expected, array $repo): void {
    // Use reflection to make isUnique "public".
    $reflection_is_unique = new \ReflectionMethod($this->drupaleasyRepositoriesService, 'isUnique');
    // This next line is not necessary for PHP >= 8.1.
    $reflection_is_unique->setAccessible(TRUE);
    $actual = $reflection_is_unique->invokeArgs(
      $this->drupaleasyRepositoriesService,
      [$repo, 999]
    );

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testValidateRepositoryUrls.
   *
   * @return array<int, array<int, string|array<int, array<string, string>>>>
   *   Expected result and urls array.
   */
  public function providerTestValidateRepositoryUrls(): array {
    // Provider functions are run before setup(), so things like
    // $this->container are not available here :(.
    return [
      ['', [['uri' => '/tests/assets/batman-repo.yml']]],
      ['is not valid', [['uri' => '/tests/assets/batman-repo.ym']]],
      ['was not found', [['uri' => '/tests/assets/flash-repo.yml']]],
    ];
  }

  /**
   * Tests our service's validateRepositoryUrls method.
   *
   * @param string $expected
   *   The expected result.
   * @param array<int, array<string, string>> $urls
   *   The URLs to test with.
   *
   * @covers \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService::validateRepositoryUrls
   * @test
   * @dataProvider providerTestValidateRepositoryUrls
   */
  public function testValidateRepositoryUrls(string $expected, array $urls): void {
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler_service */
    $module_handler_service = $this->container->get('module_handler');
    $module = $module_handler_service->getModule('drupaleasy_repositories');
    $module_full_path = \Drupal::request()->getUri() . $module->getPath();
    foreach ($urls as $key => $url) {
      if (isset($url['uri'])) {
        $urls[$key]['uri'] = $module_full_path . $url['uri'];
      }
    }

    $actual = $this->drupaleasyRepositoriesService->validateRepositoryUrls($urls, 999);
    if ($expected) {
      $this->assertTrue((bool) mb_stristr($actual, $expected), "The URLs' validation does not match the expected value. Actual: {$actual}, Expected: {$expected}");
    }
    else {
      $this->assertEquals($expected, $actual, "The URLs' validation does not match the expected value. Actual: {$actual}, Expected: {$expected}");
    }

  }

  /**
   * Returns Aquaman repository metadata.
   *
   * @param string $repo_name
   *   The machine name of the repository to return.
   *
   * @return array<string, array<string, string|int>>
   *   The repository metadata array.
   */
  protected function getTestRepo(string $repo_name): array {
    switch ($repo_name) {
      case 'aquaman':
        return [
          'aquaman-repository' => [
            'label' => 'The Aquaman repository',
            'description' => 'This is where Aquaman keeps all his crime-fighting code.',
            'num_open_issues' => 6,
            'source' => 'yml_remote',
            'url' => 'http://example.com/aquaman-repo.yml',
          ],
        ];

      default:
        return [
          'superman-repository' => [
            'label' => 'The Superman repository',
            'description' => 'This is where Superman keeps all his fortress of solitude code.',
            'num_open_issues' => 0,
            'source' => 'yml_remote',
            'url' => 'http://example.com/superman-repo.yml',
          ],
        ];
    }
  }

}
