<?php

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure DrupalEasy Repositories settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The plugin.manager.drupaleasy_repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected DrupaleasyRepositoriesPluginManager $repositoriesManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager $drupaleasy_repositories_manager
   *   The DrupaleasyRepositories plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DrupaleasyRepositoriesPluginManager $drupaleasy_repositories_manager) {
    parent::__construct($config_factory);
    $this->repositoriesManager = $drupaleasy_repositories_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.drupaleasy_repositories'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupaleasy_repositories_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['drupaleasy_repositories.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $repositories_config = $this->config('drupaleasy_repositories.settings');

    $repositories = $this->repositoriesManager->getDefinitions();
    uasort($repositories, function ($a, $b) {
      return Unicode::strcasecmp($a['label'], $b['label']);
    });
    $repository_options = [];
    foreach ($repositories as $repository => $definition) {
      $repository_options[$repository] = $definition['label'];
    }

    $form['repositories'] = [
      '#type' => 'checkboxes',
      '#options' => $repository_options,
      '#title' => $this->t('Repositories'),
      '#default_value' => $repositories_config->get('repositories') ?: [],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('drupaleasy_repositories.settings')
      ->set('repositories', $form_state->getValue('repositories'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
