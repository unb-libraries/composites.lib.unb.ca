<?php

/**
 * @file
 * Contains del_all_styles.php.
 *
 * Deletes ALL image styles.
 */

// Load all styles.
$result = \Drupal::entityQuery("image_style")
  ->accesscheck(false)
  ->execute();

$storage_handler = \Drupal::entityTypeManager()->getStorage("image_style");
$entities = $storage_handler->loadMultiple($result);
$storage_handler->delete($entities);
