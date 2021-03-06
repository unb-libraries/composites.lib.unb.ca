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
    $halt_routes = [
      'user.page',
      'user.pass',
      'user.register',
      'user.reset.form',
    ];

    foreach ($halt_routes as $halt_route) {
      $route = $collection->get($halt_route);
      $usr = \Drupal::currentUser();

      // Deny route access if user is visitor or content editor.
      if (!$usr->isAuthenticated() or in_array('content_editor', $usr->getRoles())) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
