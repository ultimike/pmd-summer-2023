<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent;
use Drupal\node\NodeInterface;

/**
 * Service class to help with CRUD of nodes of type repository.
 */
final class DrupaleasyRepositoriesService {

  use StringTranslationTrait;

  /**
   * Contructor for our service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManagerDrupaleasyRepositories
   *   The Drupaleasy Repositories plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher
   *   The container aware event dispatcher.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param bool $dryRun
   *   If TRUE, does not save/delete any repository nodes.
   */
  public function __construct(
    protected PluginManagerInterface $pluginManagerDrupaleasyRepositories,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ContainerAwareEventDispatcher $eventDispatcher,
    protected CacheBackendInterface $cache,
    protected TimeInterface $time,
    protected bool $dryRun = FALSE
    ) {
  }

  /**
   * Get repository help text from each enabled plugin.
   *
   * @return string
   *   The help text.
   */
  public function getValidatorHelpText(): string {
    $repository_plugins = [];

    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];
    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
      }
    }

    $help = [];
    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
    foreach ($repository_plugins as $repository_plugin) {
      $help[] = $repository_plugin->validateHelpText();
    }

    return implode(' ', $help);
  }

  /**
   * Validate repository URLs.
   *
   * Validate the URLs are valid based on the enabled plugins and ensure they
   * haven't been added by another user.
   *
   * @param array<mixed> $urls
   *   The urls to be validated.
   * @param int $uid
   *   The user id of the user submitting the URLs.
   *
   * @return string
   *   Errors reported by plugins.
   */
  public function validateRepositoryUrls(array $urls, int $uid): string {
    $errors = [];
    $repository_plugins = [];

    // Get IDs all DrupaleasyRepository plugins (enabled or not).
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

    // Instantiate each enabled DrupaleasyRepository plugin (and confirm that
    // at least one is enabled).
    $atLeastOne = FALSE;
    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {
        $atLeastOne = TRUE;
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
      }
    }
    if (!$atLeastOne) {
      return 'There are no enabled repository plugins';
    }

    // Loop around each Repository URL and attempt to validate.
    foreach ($urls as $url) {
      if (is_array($url)) {
        if ($uri = trim($url['uri'])) {
          $is_valid_url = FALSE;
          // Check to see if the URI is valid for any enabled plugins.
          /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
          foreach ($repository_plugins as $repository_plugin) {
            if ($repository_plugin->validate($uri)) {
              $is_valid_url = TRUE;
              $repo_info = $repository_plugin->getRepo($uri);
              if ($repo_info) {
                if (!$this->isUnique($repo_info, $uid)) {
                  $errors[] = $this->t('The repository url %uri has been added by another user.', ['%uri' => $uri]);
                }
                break;
              }
              else {
                $errors[] = $this->t('The repository url %uri was not found.', ['%uri' => $uri]);
              }
            }
          }
          if (!$is_valid_url) {
            $errors[] = $this->t('The repository url %uri is not valid.', ['%uri' => $uri]);
          }
        }
      }
    }

    if ($errors) {
      return implode(' ', $errors);
    }
    // No errors found.
    return '';
  }

  /**
   * Update the repository nodes for a given account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account who will have their repository nodes updated.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(EntityInterface $account): bool {
    // Build the cache ID for this user.
    $cid = 'drupaleasy_repositories:repositories:' . $account->id();
    // Get (if any) the cached item for this user.
    $cache = $this->cache->get($cid);
    if ($cache) {
      $repos_metadata = $cache->data;
    }
    else {
      $repos_metadata = [];
      $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

      foreach ($repository_plugin_ids as $repository_plugin_id) {
        if (!empty($repository_plugin_id)) {
          /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
          $repository_plugin = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
          // Loop through repository urls for this account.
          foreach ($account->field_repository_url ?? [] as $url) {
            // Check if URL validates for the current repository plugin.
            if ($repository_plugin->validate($url->uri)) {
              if ($repo_metadata = $repository_plugin->getRepo($url->uri)) {
                $repos_metadata += $repo_metadata;
              }
            }
          }
        }
      }

      // Save the generated data in the cache.
      $this->cache->set($cid, $repos_metadata, Cache::PERMANENT, ['user:' . $account->id()]);
    }

    return $this->updateRepositoryNodes($repos_metadata, $account) ||
      $this->deleteRepositoryNodes($repos_metadata, $account);
  }

  /**
   * Update repository nodes for a given account.
   *
   * @param array<string, array<string, string|int>> $repos_info
   *   The repository metadata from the sources.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function updateRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    if (!$repos_info) {
      return TRUE;
    }

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($repos_info as $key => $repo_info) {
      // Calculate a has value.
      $hash = md5(serialize($repo_info));

      // Look for repository nodes from this user with a matching machine name.
      $query = $node_storage->getQuery();
      $query->condition('type', 'repository')
        ->condition('uid', $account->id())
        ->condition('field_machine_name', $key)
        ->condition('field_source', $repo_info['source']);
      $results = $query->accessCheck(FALSE)->execute();

      if ($results) {
        // A matching repository node exists, so we load it.
        /** @var \Drupal\node\NodeInterface $node */
        $node = $node_storage->load(reset($results));

        // Check the hash to see if we need to update it.
        if ($hash != $node->get('field_hash')->value) {
          // Something has changed, update the node.
          $node->setTitle((string) $repo_info['label']);
          $node->set('field_description', $repo_info['description']);
          $node->set('field_machine_name', $key);
          $node->set('field_number_of_issues', $repo_info['num_open_issues']);
          $node->set('field_source', $repo_info['source']);
          $node->set('field_url', $repo_info['url']);
          $node->set('field_hash', $hash);
          if (!$this->dryRun) {
            $node->save();
            $this->repoUpdated($node, 'updated');
          }
        }
      }
      else {
        // Create a new repository node.
        /** @var \Drupal\node\NodeInterface $node */
        $node = $node_storage->create([
          'uid' => $account->id(),
          'type' => 'repository',
          'title' => $repo_info['label'],
          'field_description' => $repo_info['description'],
          'field_machine_name' => $key,
          'field_number_of_issues' => $repo_info['num_open_issues'],
          'field_source' => $repo_info['source'],
          'field_url' => $repo_info['url'],
          'field_hash' => $hash,
        ]);
        if (!$this->dryRun) {
          $node->save();
          $this->repoUpdated($node, 'created');
        }
      }
    }

    return FALSE;
  }

  /**
   * Delete repository nodes deleted from the source for a given user.
   *
   * @param array<string, array<string, string|int>> $repos_info
   *   The repository metadata from the sources.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function deleteRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Look for repository nodes from this user whose machine name doesn't
    // match one from $repos_info.
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $account->id())
      ->condition('field_machine_name', array_keys($repos_info), "NOT IN");
    $results = $query->accessCheck(FALSE)->execute();

    if ($results) {
      $nodes = $node_storage->loadMultiple($results);
      foreach ($nodes as $node) {
        if (!$this->dryRun) {
          $node->delete();
          $this->repoUpdated($node, 'deleted');
        }
      }
    }

    return TRUE;
  }

  /**
   * Check uniqueness of a given repository.
   *
   * @param array<string, array<string, string|int>> $repo_info
   *   The repository metadata from the source.
   * @param int $uid
   *   The user id whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function isUnique(array $repo_info, int $uid): bool {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    $repo_info = array_pop($repo_info);

    // Look for repository nodes from this user whose machine name doesn't
    // match one from $repos_info.
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $uid, '<>')
      ->condition('field_url', $repo_info['url']);
    $results = $query->accessCheck(FALSE)->execute();

    return !count($results);
  }

  /**
   * Helper method to dispatch event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The repository node that was changed.
   * @param string $action
   *   The action that was performed.
   */
  protected function repoUpdated(NodeInterface $node, string $action): void {
    // Dispatch the "drupaleasy_repositories_repo_updated" event while
    // including the context information.
    $event = new RepoUpdatedEvent($node, $action);
    $this->eventDispatcher->dispatch($event, RepoUpdatedEvent::REPO_UPDATED);
  }

}
