<?php

namespace Drupal\drupaleasy_repositories\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines drupaleasy_repositories annotation object.
 *
 * @Annotation
 */
class DrupaleasyRepositories extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
