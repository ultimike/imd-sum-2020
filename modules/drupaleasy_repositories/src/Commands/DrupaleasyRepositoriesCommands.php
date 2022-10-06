<?php

namespace Drupal\drupaleasy_repositories\Commands;

use Drush\Commands\DrushCommands;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drupaleasy_repositories\Batch;
use Drush\Attributes as CLI;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class DrupaleasyRepositoriesCommands extends DrushCommands {

  /**
   * The DrupalEasy repositories manager service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected DrupaleasyRepositoriesService $repositoriesService;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The DrupalEasy repositories batch service.
   *
   * @var \Drupal\drupaleasy_repositories\Batch
   */
  protected Batch $batch;

  /**
   * The Cache Tag Invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheInvalidator;

  /**
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositories_service
   *   The DrupalEasyRepositories service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   * @param \Drupal\drupaleasy_repositories\Batch $batch
   *   The DrupalEasy repositories batch service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_invalidator
   *   The Cache Tags Invalidator service.
   */
  public function __construct(DrupaleasyRepositoriesService $repositories_service, EntityTypeManagerInterface $entity_type_manager, Batch $batch, CacheTagsInvalidatorInterface $cache_invalidator) {
    parent::__construct();
    $this->repositoriesService = $repositories_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->batch = $batch;
    $this->cacheInvalidator = $cache_invalidator;
  }

  #[CLI\Command(name: 'der:update-repositories', aliases: ['der:ur'])]
  #[CLI\Option(name: 'uid', description: 'The user ID of the user to update..')]
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
          $this->logger()->notice(dt('Repositories updated.'));
        }
      }
      else {
        $this->logger()->alert(dt('User doesn\'t exist.'));
      }
    }
    else {
      // If --uid=0 was used, then $options['uid'] will be FALSE, not null.
      if (!is_null($options['uid'])) {
        $this->logger()->alert(dt('You may not select the Anonymous user.'));
        return;
      }
      // Get list of all user IDs to check.
      $this->batch->updateAllUserRepositories(TRUE);
    }

    $this->cacheInvalidator->invalidateTags(['drupaleasy_repositories']);
  }

}
