<?php

/**
* @file
* Contains clrterms.php.
* Deletes taxonomy terms created by migration.
*/

// The vocabularies being cleared of terms.
$vids = [
  'building',
  'contributor',
  'photographer',
];

foreach ($vids as $vid) {
  entity_delete_multiple('taxonomy_term',
    \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute());
}
