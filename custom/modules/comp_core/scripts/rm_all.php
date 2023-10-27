<?php

/**
 * @file
 * Contains rm_taxo.php.
 *
 * Deletes all taxonomy terms.
 */

rm_entities('taxonomy_term');
rm_entities('node');

/**
 * {@inheritdoc}
 */
function rm_entities($type) {
  $handler = \Drupal::entityTypeManager()->getStorage($type);
  $entities = $handler->loadMultiple(\Drupal::entityQuery($type)->accesscheck(false)
  ->execute());
  $handler->delete($entities);
}
