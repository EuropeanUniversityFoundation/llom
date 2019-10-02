<?php


namespace Drupal\llom_h5p_logging\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\h5p\Event\FinishedEvent;


class H5PFinishedEvent implements EventSubscriberInterface{

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
    \Drupal::logger('H5P Event')->notice($quizData['points'].'/'.$quizData['max_points']);
    return;
  }

}
