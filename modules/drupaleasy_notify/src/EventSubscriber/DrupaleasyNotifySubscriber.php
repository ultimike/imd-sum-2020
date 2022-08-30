<?php

namespace Drupal\drupaleasy_notify\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * DrupalEasy Notify event subscriber.
 */
class DrupaleasyNotifySubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Constructs a DrupaleasyNotifySubscriber object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * RepoUpdatedEvent event handler.
   *
   * @param \Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent $event
   *   RepoUpdatedEvent event.
   */
  public function onRepoUpdated(RepoUpdatedEvent $event) {
    $this->messenger->addStatus($this->t('The repo named %repo_name has been @action (@repo_url). The repo node is owned by @author_name (@author_id).', [
      '%repo_name' => $event->node->getTitle(),
      '@repo_url' => $event->node->toLink()->getUrl()->toString(),
      '@action' => $event->action,
      '@author_id' => $event->node->uid->target_id,
      '@author_name' => $event->node->uid->entity->name->value,
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      RepoUpdatedEvent::EVENT_NAME => ['onRepoUpdated'],
    ];
  }

}
