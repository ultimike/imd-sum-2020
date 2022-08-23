<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
use Drupal\Component\Serialization\Yaml;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "yml_remote",
 *   label = @Translation("Remote .yml file"),
 *   description = @Translation("Remote .yml file that includes repository metadata.")
 * )
 */
class YmlRemote extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    $pattern = '|^https?://[a-zA-Z0-9.\-]+/[a-zA-Z0-9_\-.%/]+\.ya?ml$|';

    if (preg_match($pattern, $uri) === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://anything.anything/anything/anything.yml (or "http")';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    if (file_exists($uri)) {
      if ($file_content = file_get_contents($uri)) {
        $repo_info = Yaml::decode($file_content);
        $machine_name = array_key_first($repo_info);
        $repo = reset($repo_info);
        return $this->mapToCommonFormat($machine_name, $repo['label'], $repo['description'], $repo['num_open_issues'], $uri);
      }
    }
    return [];
  }

}
