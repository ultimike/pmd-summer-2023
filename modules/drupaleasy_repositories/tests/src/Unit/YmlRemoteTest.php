<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Unit;

use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote;
use Drupal\key\KeyRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class YmlRemoteTest extends UnitTestCase {

  /**
   * The .yml remote plugin.
   *
   * @var \Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote
   */
  protected YmlRemote $ymlRemote;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected MessengerInterface|MockObject $messenger;

  /**
   * The key repository service.
   *
   * @var \Drupal\key\KeyRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected KeyRepositoryInterface|MockObject $keyRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the messenger service.
    $this->messenger = $this->getMockBuilder(Messenger::class)
      ->disableOriginalConstructor()
      //->onlyMethods(['addStatus'])
      ->getMock();

    // See https://www.drupal.org/docs/automated-testing/phpunit-in-drupal/mocking-entities-and-services-with-phpunit-and-mocks
//    $this->messenger->expects($this->any())
//      ->method('addStatus');

//    $this->messenger
//      ->expects($this->any())
//      //->willReturn('yes')
//      ->method('addStatus');

    // Mock the key_repository service.
    $this->keyRepository = $this->getMockBuilder('\Drupal\key\KeyRepositoryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->ymlRemote = new YmlRemote([], 'yml_remote', [], $this->messenger, $this->keyRepository);
  }

  /**
   * Test that the help text returns as expected.
   *
   * @covers ::validateHelpText
   * @test
   */
  public function testValidateHelpText(): void {
    self::assertEquals('https://anything.anything/anything/anything.yml (or "http")',
                       $this->ymlRemote->validateHelpText(),
                       'Help text does not match.');
  }

  /**
   * Undocumented function.
   *
   * @return array<int, array<int, string|bool>>
   *   Test data.
   */
  public function validateProvider(): array {
    return [
      [
        'A test string',
        FALSE,
      ],
      [
        'http://www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'https://www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'https://www.mysite.com/anything.yaml',
        TRUE,
      ],
      [
        '/var/www/html/anything.yaml',
        FALSE,
      ],
      [
        'https://www.mysite.com/some%20directory/anything.yml',
        TRUE,
      ],
      [
        'https://www.my-site.com/some%20directory/anything.yaml',
        TRUE,
      ],
      [
        'https://localhost/some%20directory/anything.yaml',
        TRUE,
      ],
      [
        'https://dev.www.mysite.com/anything.yml',
        TRUE,
      ],
    ];
  }

  /**
   * Test that the validation is working.
   *
   * @dataProvider validateProvider
   *
   * @covers ::validate
   * @test
   */
  public function testValidate(string $testString, bool $expected): void {
    self::assertEquals($expected, $this->ymlRemote->validate($testString), "Validation of '{$testString}' does not return '{$expected}'.");
  }

  /**
   * Test that a repo can be read properly.
   *
   * @covers ::getRepo
   * @test
   */
  public function testGetRepo(): void {
    $repo = $this->ymlRemote->getRepo(__DIR__ . '/../../assets/batman-repo.yml');
    $repo = reset($repo);
    self::assertEquals('The Batman repository', $repo['label'], "The expected label does not match what was provided: '{$repo['label']}'.");
    self::assertEquals('This is where Batman keeps all his crime-fighting code.', $repo['description'], "The expected description does not match what was provided: '{$repo['description']}'.");
    self::assertEquals('yml_remote', $repo['source'], "The expected source does not match what was provided: '{$repo['source']}'.");
  }

}
