<?php

namespace Drupal\llom_scoring\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\h5p\Event\FinishedEvent;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Subscribe to event indicated an H5P quiz has been finished.
 */
class H5PFinishedEvent implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * Load current user on construct.
   *
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(AccountProxy $currentUser, EntityTypeManagerInterface $entity, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entity;
    $this->logger = $logger_factory->get('LLOM Scoring');
    $this->messenger = $messenger;
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[FinishedEvent::FINISHED_EVENT][] = ['onH5pFinished', 100];
    return $events;
  }

  /**
   * Listener for finished H5P.
   */
  public function onH5pFinished(FinishedEvent $event) {
    $quizData = $event->getQuizFields();

    // Get parent node for Quiz Content.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('type', 'llom_h5p_content');
    $query->condition('field_llom_assignment.h5p_content_id', $quizData['content_id']);
    $query->accessCheck(FALSE);
    $nids = $query->execute();

    // Double check if the number of nodes is 1.
    if (count($nids) != 1) {
      // If not, there is an issue in retrieving the parent node and we cannot
      // store a score; log issue to Drupal error logging.
      $this->logger->error($this->t('Cannot store result for Question ID @qid, more than 1 parent node?.', [
        '@qid' => $quizData['content_id'],
      ]));
      // Notify user.
      $this->messenger->addError($this->t('Could not store your score for the assignment'));
      return;
    }

    // Get first node.
    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));

    // Store score in the database.
    $values = [
      'uid' => $this->entityTypeManager->getStorage('user')->load($this->currentUser->id()),
      'nid' => $node,
      'score' => $quizData['points'],
      'max_score' => $quizData['max_points'],
      'time' => $quizData['finished'],
    ];
    $entity = $this->entityTypeManager->getStorage('llom_scoring')->create($values);
    $entity->save();
  }

}
