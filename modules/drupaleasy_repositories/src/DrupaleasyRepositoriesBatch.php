<?php declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Batch service class to integrate with Batch API.
 */
final class DrupaleasyRepositoriesBatch {

  use StringTranslationTrait;

  /**
   * Constructs a Batch object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleExtensionList $extensionListModule,
    private readonly DrupaleasyRepositoriesService $drupaleasyRepositoriesService,
  ) {}

  /**
   * Updates all user repositories using the Batch API.
   */
  public function updateAllUserRepositories(): void {
    $operations = [];

    // Get all active users.
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery();
    $query->condition('status', '1');
    $users = $query->accessCheck(FALSE)->execute();

    // Create a Batch API item for each user.
    foreach ($users as $uid => $user) {
      $operations[] = ['drupaleasy_update_repositories_batch_operation', [$uid]];
    }
    $batch = [
      'operations' => $operations,
      'finished' => 'drupaleasy_update_all_repositories_finished',
      'file' => $this->extensionListModule->getPath('drupaleasy_repositories') . '/drupaleasy_repositories.batch.inc',
    ];

    // Submit the batch for processing.
    batch_set($batch);
  }

  /**
   * Batch process callback from updating user repositories.
   *
   * @param int $uid
   *   User ID to update.
   * @param array|\ArrayAccess $context
   *   Context for operations. We do not want to type hint this as an array or
   *   an object as sometimes it is an array (when calling from a form) and
   *   sometimes it is an object (when calling from Drush).
   */
  public function updateRepositoriesBatch(int $uid, array|\ArrayAccess &$context): void {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $account = $user_storage->load($uid);
    $this->drupaleasyRepositoriesService->updateRepositories($account);
    $context['results'][] = $uid;
    $context['results']['num']++;
    $context['message'] = $this->t('Updated repositories belonging to "@username".',
      ['@username' => $account->label()]
    );
  }

}
