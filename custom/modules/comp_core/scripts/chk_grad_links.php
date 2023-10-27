<?php

/**
 * @file
 * Contains chk_grad_links.php.
 *
 * Checks all composite for reachable graduation ceremony links, and reports
 * fails.
 */

use Drupal\node\Entity\Node;

// Search for all composite node ids.
$cids = Drupal::entityQuery('node')
  ->condition('type', 'composite')->accesscheck(false)
  ->execute();

// Load and process all composites.
foreach ($cids as $cid) {
  $composite = Node::load($cid);
  $grad_url = $composite->field_grad_ceremony->getValue()[0]['uri'];
  $title = $composite->getTitle();

  if ($grad_url and !url_valid($grad_url)) {
    echo "\n$title";
  }
}

echo "\n";
