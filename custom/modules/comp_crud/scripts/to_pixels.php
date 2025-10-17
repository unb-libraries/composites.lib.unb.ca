<?php
/**
 * Update subject nodes: multiply coordinate fields by display resolution and store as integer pixels.
 *
 *  - Dry run (no saves): drush scr scripts/to_pixels.php
 *  - Apply changes:      drush scr scripts/to_pixels.php -- --apply
 *  - Single subject:      drush scr scripts/to_pixels.php -- --nid=123 [--apply]
 *
 * Behavior:
 *  - Operates on nodes of type 'subject'.
 *  - Reads field_disp_resolution from the composite referenced by subject->field_composite.
 *    If the composite's field_disp_resolution is missing/invalid the script falls back
 *    to the default resolution (72). It no longer falls back to the subject's field.
 *  - For each mapping source_field -> target_field: computes floor(source_value * resolution + offset)
 *    where per-node offset is derived from the composite linked by subject->field_composite:
 *      xOff = xBase * width  / 1000  (xBase = 15)
 *      yOff = yBase * height / 1000  (yBase = 20)
 *    width and height are taken from the image referenced by field_image on the composite node.
 *  - Offsets are added to x coordinates (fields ending in _x) and y coordinates (ending in _y).
 *  - Writes the integer into target_field only if the computed value differs from current.
 *  - By default it's a dry-run; add --apply to persist changes.
 */

use Drupal\node\Entity\Node;
use Drupal\file\FileInterface;

if (PHP_SAPI !== 'cli') {
  echo "This script must be run from the command line (drush scr recommended).\n";
  exit(1);
}

// Parse CLI args. Drush forwards arguments after a "--" so look in $argv.
$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
$apply = in_array('--apply', $argv, true);

// Parse optional --nid argument to target a single node.
// Accepts: --nid=123 or --nid 123
$nid = null;
foreach ($argv as $i => $arg) {
  if (strpos($arg, '--nid=') === 0) {
    $val = substr($arg, strlen('--nid='));
    if (is_numeric($val)) {
      $nid = (int) $val;
      break;
    }
  }
  if ($arg === '--nid' && isset($argv[$i + 1]) && is_numeric($argv[$i + 1])) {
    $nid = (int) $argv[$i + 1];
    break;
  }
}

// Default resolution to use when composite field_disp_resolution is missing or invalid.
$default_resolution = 72.0;

// Base constants for offsets
$xBase = 15.0; // used for xOff = xBase * width / 1000
$yBase = 20.0; // used for yOff = yBase * height / 1000

// Edit this map if your source fields are named differently.
// Keys = source field name (e.g. coordinate in inches)
// Values = destination pixel field name (will be set to floor(source * resolution + offset))
$fields_map = [
  'field_top_x'    => 'field_top_x_pixels',
  'field_top_y'    => 'field_top_y_pixels',
  'field_bottom_x' => 'field_bottom_x_pixels',
  'field_bottom_y' => 'field_bottom_y_pixels',
];

$batch_size = 50;
$updated_count = 0;
$processed_count = 0;
$skipped_count = 0;

echo "Starting subject nodes display-resolution -> pixels update (" . ($apply ? "APPLYING CHANGES" : "DRY RUN") . ")\n";

$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$file_storage = \Drupal::entityTypeManager()->getStorage('file');

if ($nid !== null) {
  // Load and validate the single node.
  $node = $node_storage->load($nid);
  if (!$node) {
    echo "Node with nid {$nid} not found. Exiting.\n";
    exit(1);
  }
  if ($node->getType() !== 'subject') {
    echo "Node with nid {$nid} is of type '" . $node->getType() . "' (expected 'subject'). Exiting.\n";
    exit(1);
  }
  $nids_all = [$nid];
  $total = 1;
  echo "Targeting single subject node: nid {$nid}\n";
}
else {
  // Load entity query for nodes of type subject
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'subject')
    ->accessCheck(FALSE);

  // Execute to get nids
  $nids_all = $query->execute();
  $total = count($nids_all);
  if ($total === 0) {
    echo "No nodes of type 'subject' found.\n";
    exit(0);
  }

  echo "Found {$total} subject nodes. Processing in batches of {$batch_size}.\n";
}

$nids_chunks = array_chunk($nids_all, $batch_size);
foreach ($nids_chunks as $chunk_index => $nids) {
  /** @var \Drupal\node\NodeInterface[] $nodes */
  $nodes = $node_storage->loadMultiple($nids);

  foreach ($nodes as $node) {
    $processed_count++;

    // Determine width and height (in pixels) and display resolution from the composite referenced by this subject.
    $width = null;
    $height = null;
    $composite_node = null;

    // Ensure field_composite exists, then read the target_id explicitly (do not use ->entity).
    if ($node->hasField('field_composite') && !$node->get('field_composite')->isEmpty()) {
      $values = $node->get('field_composite')->getValue();
      if (!empty($values) && isset($values[0]['target_id']) && $values[0]['target_id']) {
        $cid = (int) $values[0]['target_id'];
        // Load the referenced composite node explicitly from storage.
        $loaded = $node_storage->load($cid);
        if ($loaded) {
          $composite_node = $loaded;
        }
      }
    }

    // Try to read width/height and display resolution from the composite's field_image and field_disp_resolution
    $composite_resolution = null;
    if ($composite_node) {
      // Read resolution from composite if present and numeric (field_disp_resolution)
      if ($composite_node->hasField('field_disp_resolution') && !$composite_node->get('field_disp_resolution')->isEmpty()) {
        $res_raw_comp = $composite_node->get('field_disp_resolution')->value;
        $composite_resolution = is_numeric($res_raw_comp) ? (float) $res_raw_comp : null;
      }

      // Resolve image of composite to find width/height (handle file or media).
      if ($composite_node->hasField('field_image') && !$composite_node->get('field_image')->isEmpty()) {
        $image_field = $composite_node->get('field_image');
        $file = null;

        // Prefer reading target_id explicitly to avoid any ambiguity
        $img_values = $image_field->getValue();
        if (!empty($img_values) && isset($img_values[0]['target_id']) && $img_values[0]['target_id']) {
          $fid = (int) $img_values[0]['target_id'];
          $file = $file_storage->load($fid);
        } else {
          // fallback: try entity if available
          $target = $image_field->entity;
          if ($target && ($target instanceof FileInterface)) {
            $file = $target;
          } elseif ($target && $target->getEntityTypeId() === 'media') {
            if ($target->hasField('field_media_image') && !$target->get('field_media_image')->isEmpty()) {
              $file = $target->get('field_media_image')->entity;
            } elseif ($target->hasField('image') && !$target->get('image')->isEmpty()) {
              $file = $target->get('image')->entity;
            }
          }
        }

        if ($file && $file->getFileUri()) {
          $uri = $file->getFileUri();
          try {
            $realpath = \Drupal::service('file_system')->realpath($uri);
            if ($realpath && file_exists($realpath)) {
              $size = @getimagesize($realpath);
              if ($size && isset($size[0]) && isset($size[1])) {
                $width = (int) $size[0];
                $height = (int) $size[1];
              }
            }
          } catch (\Exception $e) {
            // ignore and leave width/height null
          }
        }
      }
    }

    // Determine resolution to use: rely on composite field_disp_resolution (if valid), otherwise default.
    $resolution = null;
    $resolution_note = '';
    if ($composite_resolution !== null && $composite_resolution > 0) {
      $resolution = $composite_resolution;
      $resolution_note = "(from composite field_disp_resolution)";
    } else {
      $resolution = $default_resolution;
      $resolution_note = "(used default {$default_resolution})";
    }

    // If width/height not found, treat offsets as zero (but warn)
    if ($width === null || $height === null) {
      $width_info = $width === null ? 'unknown' : $width;
      $height_info = $height === null ? 'unknown' : $height;
      echo "Processing subject nid {$node->id()} with resolution {$resolution} {$resolution_note} (composite image size: width={$width_info}, height={$height_info} -> offsets will be 0)\n";
      $width = $width ?: 0;
      $height = $height ?: 0;
    } else {
      echo "Processing subject nid {$node->id()} with resolution {$resolution} {$resolution_note} (composite image size: {$width}x{$height})\n";
    }

    // Compute offsets (in pixels)
    $xOff = ($xBase * $width) / 1000.0;
    $yOff = ($yBase * $height) / 1000.0;

    // show computed offsets in dry-run output
    echo "  - xOff = {$xBase} * {$width} / 1000 = {$xOff}; yOff = {$yBase} * {$height} / 1000 = {$yOff}\n";

    $node_changed = false;
    $node_nid = $node->id();

    foreach ($fields_map as $source_field => $target_field) {
      // Guard source field existence
      if (!$node->hasField($source_field)) {
        $skipped_count++;
        echo "  - {$source_field}: field does not exist on subject, skipping\n";
        continue;
      }

      $source_value_raw = $node->get($source_field)->value;
      // If source value is empty or not numeric, skip.
      if ($source_value_raw === NULL || $source_value_raw === '' || !is_numeric($source_value_raw)) {
        $skipped_count++;
        echo "  - {$source_field}: empty or non-numeric, skipping\n";
        continue;
      }
      $source_value = (float) $source_value_raw;

      // Determine whether this is an X or Y coordinate to add the correct offset.
      $is_x = (bool) preg_match('/_x$/', $source_field) || (bool) preg_match('/_x$/', $target_field);
      $is_y = (bool) preg_match('/_y$/', $source_field) || (bool) preg_match('/_y$/', $target_field);

      $offset = 0.0;
      if ($is_x) {
        $offset = $xOff;
      } elseif ($is_y) {
        $offset = $yOff;
      } else {
        // no offset for fields that are neither explicitly x nor y
        $offset = 0.0;
      }

      // Compute final pixel value: floor(source_in_inches * resolution + offset)
      $computed = (int) floor($source_value * $resolution + $offset);

      // Guard target field existence before reading current value
      if (!$node->hasField($target_field)) {
        // If target field doesn't exist, count as skipped and show message.
        $skipped_count++;
        echo "  - {$target_field}: target field does not exist on subject, skipping\n";
        continue;
      }

      $current = $node->get($target_field)->value;
      $current_int = is_numeric($current) ? (int) $current : null;

      if ($current_int === $computed) {
        echo "  - {$target_field}: {$computed} (no change)\n";
        continue;
      }

      echo "  - {$target_field}: will change from " . var_export($current_int, true) . " to {$computed} (source={$source_value}, resolution={$resolution}, offset=" . number_format($offset, 3) . ")\n";
      if ($apply) {
        $node->set($target_field, $computed);
        $node_changed = true;
      }
      $updated_count++;
    }

    if ($apply && $node_changed) {
      try {
        $node->save();
        echo "  -> Saved subject node {$node_nid}.\n";
      } catch (\Exception $e) {
        echo "  -> Failed to save subject node {$node_nid}: " . $e->getMessage() . "\n";
      }
    }
  }
}

echo "Done. Processed: {$processed_count}, Updated fields: {$updated_count}, Skipped: {$skipped_count}\n";