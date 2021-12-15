<?php

/**
 * @file
 * Contains del_crop_styles.php.
 *
 * Deletes ALL subject crop image styles.
 */

use Drupal\image\Entity\ImageStyle;

// Load all styles.
$styles = \Drupal::entityQuery('image_style')
  ->execute();

// Run through styles.
foreach ($styles as $style_name) {
  // If the style correponds to a composite crop...
  if (strpos($style_name, 'composite_crop') !== FALSE) {
    // Load, delete, and flush style.
    $style = ImageStyle::load($style_name);
    $style->delete();
    $style->flush();
    echo "Deleted image style [$style_name]";
  }
}
