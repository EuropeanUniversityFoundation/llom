<?php

namespace Drupal\llom_redirect\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Custom Route subscriber.
 */
class CustomRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.logout')) {
      $route->setDefaults([
        '_controller' => '\Drupal\llom_redirect\Controller\CustomUserLogoutController::logout',
      ]);
      $route->setOptions([
        'no_cache' => TRUE 
      ]);
    }
  }

}
