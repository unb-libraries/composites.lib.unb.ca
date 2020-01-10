<?php

namespace Drupal\comp_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Routing controller for composites.lib.unb.ca.
 */
class CompCoreController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function home() {
    $element = [
      '#theme' => 'comp_landing',
      '#attributes' => [],
    ];
    return $element;
  }

}
