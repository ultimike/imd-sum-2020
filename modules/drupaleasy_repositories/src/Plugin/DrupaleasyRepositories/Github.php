<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
use Github\Client;
use Symfony\Component\HttpClient\HttplugClient;
use Github\AuthMethod;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("Github"),
 *   description = @Translation("Github.com")
 * )
 */
class Github extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    $pattern = '|^https://github.com/[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+|';

    if (preg_match($pattern, $uri) === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://github.com/vendor/name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    // Parse the URI into its component parts.
    $all_parts = parse_url($uri);
    $parts = explode('/', $all_parts['path']);

    // Authenticate with the Github API.
    if ($this->authenticate()) {
      // Get the repo metadata from the API.
      try {
        $repo = $this->client->api('repo')->show($parts[1], $parts[2]);
      }
      catch (\Throwable $th) {
        $this->messenger->addMessage($this->t('GitHub error: @error', [
          '@error' => $th->getMessage(),
        ]));
        return [];
      }

      // Map it to a common format.
      return $this->mapToCommonFormat($repo['full_name'], $repo['name'], $repo['description'], $repo['open_issues_count'], $repo['html_url']);
    }
    else {
      // If authentication failed, return nothing.
      return [];
    }
  }

  /**
   * Authenticate with Github.
   */
  protected function authenticate(): bool {
    $this->client = Client::createWithHttpClient(new HttplugClient());
    $github_key = $this->keyRepository->getKey('github')->getKeyValues();
    try {
      // The authenticate() method does not return TRUE/FALSE, only an error if
      // unsuccessful.
      $this->client->authenticate($github_key['username'], $github_key['personal_access_token'], AuthMethod::CLIENT_ID);
    }
    catch (\Throwable $th) {
      $this->messenger->addMessage($this->t('GitHub error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return FALSE;
    }

    return TRUE;
  }

}
