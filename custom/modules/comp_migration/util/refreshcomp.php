<?php

/**
 * @file
 * Contains refreshcomp.php.
 *
 * Refresh all composites. REQUIRED for generating subject->composite references
 * after migration.
 */

use Drupal\node\Entity\Node;

// Search for all composite node ids.
$cids = Drupal::entityQuery('node')
  ->condition('type', 'composite')->execute();

// Load and save all composites.
foreach ($cids as $cid) {
  $composite = Node::load($cid);
  $composite->save();
}
