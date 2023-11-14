<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class CustomBlockCacheValuesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'block',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('drupaleasy_repositories_my_repositories_stats',
      [
        'region' => 'content',
        'id' => 'drupaleasy-repositories-my-repositories-stats',
      ]
    );
  }

  /**
   * Tests to ensure that custom cache values are present.
   *
   * @test
   */
  public function testCustomCacheValues(): void {
    // Go to the home page.
    $this->drupalGet('');

    // This demonstrates that max-age does not bubble up.
    $this->assertSession()->responseHeaderNotContains('Cache-Control', '123');

    // Demonstrate that cache tags added to our block bubble up.
    $this->assertSession()->responseHeaderContains('x-drupal-cache-tags', 'drupaleasy_repositories');
    $this->assertSession()->responseHeaderContains('x-drupal-cache-tags', 'node_list:repository');

    // Demonstrate that cache contexts added to our block bubble up.
    $this->assertSession()->responseHeaderContains('x-drupal-cache-contexts', 'timezone');
  }

}
