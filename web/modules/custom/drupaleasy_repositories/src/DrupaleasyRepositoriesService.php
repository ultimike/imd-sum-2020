<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityInterface;

/**
 * Service description.
 */
class DrupaleasyRepositoriesService {

  use StringTranslationTrait;

  /**
   * The plugin.manager.drupaleasy_repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected DrupaleasyRepositoriesPluginManager $pluginManagerDrupaleasyRepositories;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityManager;

  /**
   * The dry-run parameter.
   *
   * When set to "true", no nodes are created, updated, or deleted.
   *
   * @var bool
   */
  protected bool $dryRun = FALSE;

  /**
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories
   *   The plugin.manager.drupaleasy_repositories service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   */
  public function __construct(DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_type_manager;
  }

  /**
   * Get repository URL help text from each enabled plugin.
   *
   * @return string
   *   The help text.
   */
  public function getValidatorHelpText(): string {
    $repositories = [];
    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories') ?? [];

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        $repositories[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
      }
    }

    $help = [];

    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository */
    foreach ($repositories as $repository) {
      $help[] = $repository->validateHelpText();
    }

    if (count($help)) {
      return implode(' ', $help);
    }

    return '';
  }

  /**
   * Validate repository URLs.
   *
   * Validate the URLs are valid based on the enabled plugins and ensure they
   * haven't been added by another user. This only validates non-yml
   * repository URLs.
   *
   * @param array $urls
   *   The urls to be validated.
   * @param int $uid
   *   The user id of the user submitting the URLs.
   *
   * @return string
   *   Errors reported by plugins.
   */
  public function validateRepositoryUrls(array $urls, int $uid):string {
    $errors = [];
    $repository_services = [];

    // Get IDs of enabled DrupaleasyRepository plugins.
    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories') ?? [];
    if (!$repository_location_ids) {
      return 'There are no enabled repository plugins';
    }

    // Instantiate each enabled DrupaleasyRepository plugin.
    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        $repository_services[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
      }
    }

    // Loop around each Repository URL and attempt to validate.
    foreach ($urls as $url) {
      if (is_array($url)) {
        if ($uri = trim($url['uri'])) {
          $validated = FALSE;
          // Check to see if the URI is valid for any enabled plugins.
          /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_service */
          foreach ($repository_services as $repository_service) {
            if ($repository_service->validate($uri)) {
              $validated = TRUE;
            }
          }
          if (!$validated) {
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
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(EntityInterface $account): bool {
    // 1. Get repository metadata for each Repository URL in $account.
    $repos_info = [];
    // Use Null Coalesce Operator in case no repositories are enabled.
    // See https://wiki.php.net/rfc/isset_ternary
    $repository_location_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories') ?? [];

    foreach ($repository_location_ids as $repository_location_id) {
      if (!empty($repository_location_id)) {
        /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_location */
        $repository_location = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_location_id);
        // Loop through repository URLs.
        foreach ($account->field_repository_url ?? [] as $url) {
          // Check if the URL validates for this repository.
          if ($repository_location->validate($url->uri)) {
            // Confirm the repository exists and get metadata.
            if ($repo_info = $repository_location->getRepo($url->uri)) {
              $repos_info += $repo_info;
            }
          }
        }
      }
    }
    return $this->updateRepositoryNodes($repos_info, $account) &&
      $this->deleteRepositoryNodes($repos_info, $account);
  }

  /**
   * Update repository nodes for a given user.
   *
   * @param array $repos_info
   *   Repository info from API call.
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
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityManager->getStorage('node');

    foreach ($repos_info as $key => $info) {
      // Calculate hash value.
      $hash = md5(serialize($info));

      // Look for repository nodes from this user with matching
      // machine_name.
      /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
      $query = $node_storage->getQuery();
      $query->condition('type', 'repository')
        ->condition('uid', $account->id())
        ->condition('field_machine_name', $key)
        ->condition('field_source', $info['source'])
        ->accessCheck(FALSE);
      $results = $query->execute();

      if ($results) {
        // If we get here, a repository node exists for this repository machine name.
        /** @var \Drupal\node\NodeInterface $node */
        $node = $node_storage->load(reset($results));

        if ($hash != $node->get('field_hash')->value) {
          // Something changed, update node.
          $node->setTitle($info['label']);
          $node->set('field_description', $info['description']);
          $node->set('field_machine_name', $key);
          $node->set('field_num_open_issues', $info['num_open_issues']);
          $node->set('field_source', $info['source']);
          $node->set('field_url', $info['url']);
          $node->set('field_hash', $hash);
          if (!$this->dryRun) {
            $node->save();
            //$this->repoUpdated($node, 'updated');
          }
        }
      }
      else {
        // Repository node doesn't exist - create a new one.
        /** @var \Drupal\node\NodeInterface $node */
        $node = $node_storage->create([
          'uid' => $account->id(),
          'type' => 'repository',
          'title' => $info['label'],
          'field_description' => $info['description'],
          'field_machine_name' => $key,
          'field_num_open_issues' => $info['num_open_issues'],
          'field_source' => $info['source'],
          'field_url' => $info['url'],
          'field_hash' => $hash,
        ]);
        if (!$this->dryRun) {
          $node->save();
          //$this->repoUpdated($node, 'created');
        }
      }
    }
    return TRUE;
  }

  /**
   * Delete repository nodes deleted from the source for a given user.
   *
   * @param array $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function deleteRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityManager->getStorage('node');

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $account->id())
      ->accessCheck(FALSE);
    // We can't chain this above because $repos_info might be empty.
    if ($repos_info) {
      $query->condition('field_machine_name', array_keys($repos_info), 'NOT IN');
    }
    $results = $query->execute();
    if ($results) {
      $nodes = $node_storage->loadMultiple($results);
      /** @var \Drupal\node\NodeInterface $node */
      foreach ($nodes as $node) {
        if (!$this->dryRun) {
          $node->delete();
          //$this->repoUpdated($node, 'deleted');
        }
      }
    }
    return TRUE;
  }

}
