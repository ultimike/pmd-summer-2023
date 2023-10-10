<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'drupaleasy_repositories_repository_node_updater' queue worker.
 *
 * @QueueWorker(
 *   id = "drupaleasy_repositories_repository_node_updater",
 *   title = @Translation("Repository node updater"),
 *   cron = {"time" = 60},
 * )
 */
final class RepositoryNodeUpdater extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @return self
   *   Instance of this class.
   */
  public static function create(ContainerInterface $container, array $configuration, mixed $plugin_id, mixed $plugin_definition): RepositoryNodeUpdater {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('drupaleasy_repositories.service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Plugin constructor.
   *
   * @param array $configuration
   *   The plugin config.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositoriesService
   *   The DrupalEasy repositories service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected DrupaleasyRepositoriesService $repositoriesService, protected EntityTypeManagerInterface $entityTypeManager) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->repositoriesService = $repositoriesService;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (isset($data['uid'])) {
      // Load the user account.
      $account = $this->entityTypeManager->getStorage('user')->load($data['uid']);
      // Call our service method.
      $this->repositoriesService->updateRepositories($account);
    }
  }

}
