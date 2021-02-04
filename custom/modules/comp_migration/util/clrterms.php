<?php

/**
 * @file
 * Contains clrterms.php. Deletes taxonomy terms created by migration.
 */

// The vocabularies being cleared of terms.
$vids = [
  'building',
  'contributor',
  'photographer',
];

$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($vids as $vid) {
  $storage->delete($storage->loadMultiple(
    \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute()));
}
