<?php

/**
 * @file
 * Contains clrfiles.php. Deletes ALL managed FILES.
 */

use Drupal\file\Entity\File;

$file_storage = \Drupal::entityTypeManager()->getStorage('file');
$fids = Drupal::entityQuery('file')
  ->condition('status', 1)
  ->accesscheck(false)
  ->execute();

foreach ($fids as $fid) {
  $file = File::load($fid);
  $file->delete();
}
