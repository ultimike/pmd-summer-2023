<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DrupalEasy Repositories form.
 */
final class UpdateRepositoriesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): UpdateRepositoriesForm {
    return new static(
      $container->get('drupaleasy_repositories.service'),
      $container->get('entity_type.manager'),
      $container->get('drupaleasy_repositories.batch'),
    );
  }

  /**
   * The constructor.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositoriesService
   *   The Drupaleasy Repositories service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal core entity type manager service.
   */
  public function __construct(
    protected DrupaleasyRepositoriesService $repositoriesService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected DrupaleasyRepositoriesBatch $drupaleasyRepositoriesBatch,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupaleasy_repositories_update_repositories';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Leave blank to update all repository nodes for all users.'),
      '#required' => FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go!'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!is_null($form_state->getValue('uid')) && ((int) $form_state->getValue('uid') === 0)) {
      $form_state->setErrorByName('uid', $this->t('You may not select the Anonymous user.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($uid = $form_state->getValue('uid')) {
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');

      $account = $user_storage->load($uid);
      if ($account) {
        if ($this->repositoriesService->updateRepositories($account)) {
          $this->messenger()->addMessage($this->t('Repositories updated.'));
        }
      }
      else {
        $this->messenger()->addMessage($this->t('User does not exist.'));
      }
    }
    else {
      $this->drupaleasyRepositoriesBatch->updateAllUserRepositories();
    }
  }

}
