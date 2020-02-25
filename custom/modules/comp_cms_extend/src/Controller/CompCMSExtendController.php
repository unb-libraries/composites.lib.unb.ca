<?php

namespace Drupal\comp_cms_extend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Access\AccessResult;

/**
 * Routing controller for comp_cms_extend.
 */
class CompCMSExtendController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function addCompSubject($cid) {
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
        'type' => 'subject',
        'field_composite' => $cid,
      ]);

    $form = \Drupal::entityTypeManager()
      ->getFormObject('node', 'default')
      ->setEntity($node);

    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function onlyComposites($node) {
    // Grants custom access to composite nodes only.
    return AccessResult::allowedIf(Node::load($node)->bundle() == 'composite');
  }

}
