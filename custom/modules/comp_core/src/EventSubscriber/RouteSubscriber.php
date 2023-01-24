<?php

namespace Drupal\comp_core\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Session\AccountProxy;
use Drupal\User\Entity\User;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  /**
   * Current user session.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $currentUserSession;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param Drupal\Core\Session\AccountProxy $currentUserSession
   *   The current user session.
   */
  public function __construct(AccountProxy $currentUserSession) {
    $this->currentUserSession = $currentUserSession;
  }

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
      $usr = User::load($this->currentUserSession->id());

      // Deny route access if user is visitor or content editor.
      if (!$usr->isAuthenticated() or in_array('content_editor', $usr->getRoles())) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
