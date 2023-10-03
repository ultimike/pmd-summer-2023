<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
use Github\AuthMethod;
use Github\Client;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("GitHub"),
 *   description = @Translation("GitHub.com")
 * )
 */
final class Github extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    $pattern = '|^https://github.com/[a-zA-Z0-9_\-/]+/[a-zA-Z0-9_\-/]+$|';

    if (preg_match($pattern, $uri) === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://github.com/vendor/name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    // Parse the incoming $uri into its component parts.
    $all_parts = parse_url($uri);
    $path_parts = explode('/', $all_parts['path']);

    // Set up authentication with the GitHub API.
    $this->setAuthentication();

    // Get the repo metadata from the GitHub API.
    try {
      $repo = $this->client->api('repo')->show($path_parts[1], $path_parts[2]);
    }
    catch (\Throwable $th) {
      //$this->messenger->addMessage($this->t('GitHub error: @error', [
      //  '@error' => $th->getMessage(),
      //]));
      return [];
    }

    // Map it to a common format.
    return $this->mapToCommonFormat($repo['full_name'], $repo['name'], $repo['description'], $repo['open_issues_count'], $repo['html_url']);
  }

  /**
   * Authenticate with GitHub.
   */
  protected function setAuthentication(): void {
    $this->client = new Client();
    // The authenticate() method does not actually call the GitHub API,
    // rather it only stores the authentication info in $client for use when
    // $client makes an API call that requires authentication.
    $this->client->authenticate('ultimike', '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', AuthMethod::CLIENT_ID);
  }

}
