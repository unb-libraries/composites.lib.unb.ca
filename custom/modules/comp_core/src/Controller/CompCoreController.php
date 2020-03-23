<?php

namespace Drupal\comp_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Routing controller for composites.lib.unb.ca.
 */
class CompCoreController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function home() {
    return new RedirectResponse("/introduction");
  }

}
