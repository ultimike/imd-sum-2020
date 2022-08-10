<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service description.
 */
class DrupaleasyRepositoriesService {

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
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories
   *   The plugin.manager.drupaleasy_repositories service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   */
  public function __construct(DrupaleasyRepositoriesPluginManager $plugin_manager_drupaleasy_repositories, ConfigFactoryInterface $config_factory) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
    $this->configFactory = $config_factory;
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

}
