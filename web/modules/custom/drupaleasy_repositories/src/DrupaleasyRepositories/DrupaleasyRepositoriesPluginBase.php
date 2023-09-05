<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for drupaleasy_repositories plugins.
 */
abstract class DrupaleasyRepositoriesPluginBase extends PluginBase implements DrupaleasyRepositoriesInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $url): bool {
    return FALSE;
  }

  /**
   * Build array of a single repository.
   *
   * @param string $machine_name
   *   The machine name of the repository.
   * @param string $label
   *   The friendly name of the reposotory.
   * @param string $description
   *   The description of the repository.
   * @param int $num_open_issues
   *   The number of open issues in the repository.
   * @param string $uri
   *   The URI of the repository.
   *
   * @return array<string, array<string, string>>
   *   An array containing info about a single repository.
   */
  protected function mapToCommonFormat(string $machine_name, string $label, string|null $description, int $num_open_issues, string $uri): array {
    $repo_info[$machine_name] = [
      'label' => $label,
      'description' => $description,
      'num_open_issues' => $num_open_issues,
      'source' => $this->getPluginId(),
      'url' => $uri,
    ];
    return $repo_info;
  }

}
