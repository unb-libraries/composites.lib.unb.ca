<?php

/**
 * @file
 * Contains refreshsub.php.
 *
 * Refresh all subjects. REQUIRED for generating subject->composite references
 * after migration.
 */

use Drupal\node\Entity\Node;

// Search for all subject node ids.
$sids = Drupal::entityQuery('node')
  ->condition('type', 'subject')->accesscheck(false)
  ->execute();

// Load and save all subjects.
foreach ($sids as $sid) {
  echo "Processing: $sid\n";
  $subject = Node::load($sid);
  $subject->save();
  echo "Done: $sid\n";
}
