<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
final class DrupaleasyRepositoriesCommands extends DrushCommands {

  /**
   * Constructs a DrupaleasyRepositoriesCommands object.
   */
  public function __construct(
    private readonly DrupaleasyRepositoriesService $repositoriesService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly DrupaleasyRepositoriesBatch $drupaleasyRepositoriesBatch
  ) {
    parent::__construct();
  }

  /**
    * Update user repositories.
    *
    * This command will update all user repositories or all repositories for a
    * single user.
    *
    * @param array<string, null|int> $options
    *   An associative array of options whose values come from cli, aliases,
    *   config, etc.
    */
  #[CLI\Command(name: 'der:update-repositories', aliases: ['der:ur'])]
  #[CLI\Option(name: 'uid', description: 'The user ID of the user to update.')]
  #[CLI\Help(description: 'Update user repositories.', synopsis: 'This command will update all user repositories or all repositories for a single user.')]
  #[CLI\Usage(name: 'der:update-repositories --uid=2', description: 'Update a user\'s repositories.')]
  #[CLI\Usage(name: 'der:update-repositories', description: 'Update all user repositories.')]
  public function updateRepositories(array $options = ['uid' => NULL]): void {
    if (!empty($options['uid'])) {
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');

      $account = $user_storage->load($options['uid']);
      if ($account) {
        if ($this->repositoriesService->updateRepositories($account)) {
          $this->logger()->notice(dt('Repositories checked for updates.'));
        }
      }
      else {
        $this->logger()->notice(dt('User does not exist.'));
      }
    }
    else {
      // If --uid=0 was used, then $options['uid'] will be FALSE, not null.
      if (!is_null($options['uid'])) {
        $this->logger()->alert(dt('You may not select the Anonymous user.'));
        return;
      }
      // Get list of all user IDs to check.
      $this->drupaleasyRepositoriesBatch->updateAllUserRepositories(TRUE);
    }

  }

}
