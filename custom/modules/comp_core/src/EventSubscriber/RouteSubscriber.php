<?php

namespace Drupal\comp_core\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.pass')) {
      if (!\Drupal::currentUser()->isAuthenticated()) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
