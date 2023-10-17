<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\Component\Serialization\Yaml;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "yml_remote",
 *   label = @Translation("Yml remote"),
 *   description = @Translation("Remote .yml file that includes repository metadata.")
 * )
 */
final class YmlRemote extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    $pattern = '|^https?://[a-zA-Z0-9.\-]+/[a-zA-Z0-9_\-.%/]+\.ya?ml$|';

    if (preg_match($pattern, $uri) === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://anything.anything/anything/anything.yml (or "http")';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    // Temporarily set the PHP error handler to a custom one. If there are any
    // E_WARNINGs, then TRUE to disable default PHP error handler.
    // This is basically telling PHP that we are going handle errors of type
    // E_WARNING until we say otherwise.
    set_error_handler(function () {
      // If FALSE is returned, then the default PHP error handler is run as
      // well.
      return TRUE;
    }, E_WARNING);

    // If file($uri) fails, it will throw a PHP E_WARNING error that we want to
    // handle ourselves.
    if (file($uri)) {
      // Restore the default PHP error handler.
      restore_error_handler();
      if ($file_content = file_get_contents($uri)) {
        // Convert file contents from Yaml to PHP array.
        $repo_info = Yaml::decode($file_content);
        $machine_name = array_key_first($repo_info);
        $repo = reset($repo_info);

        $this->messenger->addStatus($this->t('The repository has been found.'));

        // Convert metadata into a common format.
        return $this->mapToCommonFormat($machine_name, $repo['label'], $repo['description'], $repo['num_open_issues'], $uri);
      }
      restore_error_handler();
      return [];
    }
    else {
      restore_error_handler();
      return [];
    }
  }

}
