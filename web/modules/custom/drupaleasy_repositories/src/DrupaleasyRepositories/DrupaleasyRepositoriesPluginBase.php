<?php

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for drupaleasy_repositories plugins.
 */
abstract class DrupaleasyRepositoriesPluginBase extends PluginBase implements DrupaleasyRepositoriesInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * Default implementation to validate repository URLs.
   *
   * @param string $uri
   *   The URI to validate.
   *
   * @return bool
   *   Returns FALSE is not valid.
   */
  public function validate(string $uri): bool {
    return FALSE;
  }

}
