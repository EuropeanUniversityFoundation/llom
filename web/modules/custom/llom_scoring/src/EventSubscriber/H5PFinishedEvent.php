<?php


namespace Drupal\llom_scoring\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\h5p\Event\FinishedEvent;
use Drupal\Core\Session\AccountProxy;

class H5PFinishedEvent implements EventSubscriberInterface{

  private $currentUser;
  private $entityTypeManager;
  private $logger;
  private $messenger;

  /**
   * Load current user on construct
   *
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
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
  public static function getSubscribedEvents()
  {
    $events[FinishedEvent::FINISHED_EVENT][] = ['onH5PFinished', 100];
    return $events;
  }

  public function onH5PFinished(FinishedEvent $event){
    $quizData = $event->getQuizFields();
    dsm($quizData);

    //get parent node for Quiz Content
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('type','llom_h5p_content');
    $query->condition('field_llom_assignment.h5p_content_id', $quizData['content_id']);
    $nids = $query->execute();

    //double check if the number of nodes is 1
    if(count($nids) != 1){
      //If not, there is an issue in retrieving the parent node and we can't store a score
      //Log issue to Drupal error logging
      $this->logger->error(t('Cannot store result for Question ID @qid, more than 1 parent node?.', array('@qid' => $quizData['content_id'])));
      //Notify user
      $this->messenger->addError(t('Could not store your score for the assignment'));
      return;
    }

    //get first node
    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));

    //Store score in the database
    $values = array(
      'uid' => $this->entityTypeManager->getStorage('user')->load($this->currentUser->id()),
      'nid' => $node,
      'score' => $quizData['points'],
      'max_score' => $quizData['max_points'],
      'time' => $quizData['finished'],
    );
    $entity = $this->entityTypeManager->getStorage('llom_scoring')->create($values);
    $entity->save();

    return;
  }

}
