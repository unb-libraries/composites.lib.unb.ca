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

  /**
   * {@inheritdoc}
   */
  public function about() {
    $element = [
      '#theme' => 'comp_about',
      '#attributes' => [],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function ack() {
    $element = [
      '#theme' => 'comp_ack',
      '#attributes' => [],
    ];
    return $element;
  }

}
