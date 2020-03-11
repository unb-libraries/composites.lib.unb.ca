<?php

/**
 * @file
 * Contains refreshsub.php.
 *
 * Refresh all composites. REQUIRED for generating subject->composite references
 * after migration.
 */

use Drupal\node\Entity\Node;

// Search for all composite node ids.
$sids = Drupal::entityQuery('node')
  ->condition('type', 'subject')->execute();

// Load and save alll composites.
foreach ($sids as $sid) {
  echo "Processing: $sid\n";
  $subject = Node::load($sid);
  $subject->save();
  echo "Done: $sid\n";
}
