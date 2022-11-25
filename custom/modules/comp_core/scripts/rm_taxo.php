<?php

/**
 * @file
 * Contains rm_taxo.php.
 *
 * Deletes all taxonomy terms.
 */

rm_entities('taxonomy_term');

/**
 * {@inheritdoc}
 */
function rm_entities($type) {
  $handler = \Drupal::entityTypeManager()->getStorage($type);
  $entities = $handler->loadMultiple(\Drupal::entityQuery($type)->execute());
  $handler->delete($entities);
}
