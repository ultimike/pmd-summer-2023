<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for drupaleasy_repositories plugins.
 */
interface DrupaleasyRepositoriesInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * URL validator.
   *
   * @param string $uri
   *   The URL to validate.
   *
   * @return bool
   *   Returns TRUE if valid.
   */
  public function validate(string $uri): bool;

  /**
   * Returns help text for the plugin's required URL pattern.
   *
   * @return string
   *   The help text to display on the user profile page.
   */
  public function validateHelpText(): string;

  /**
   * Queries the repository service for metadata about a repository.
   *
   * @param string $url
   *   The URL of the repository we're looking for.
   *
   * @return array<string, array<string, string>>
   *   The metadata.
   */
  public function getRepo(string $url): array;

}
