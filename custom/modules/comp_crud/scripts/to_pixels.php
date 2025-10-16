<?php
/**
 * Update composite nodes: multiply coordinate fields by DPI and store as integer pixels.
 *
 *  - Dry run (no saves): drush scr scripts/update_composite_pixel_fields.php
 *  - Apply changes:      drush scr scripts/update_composite_pixel_fields.php --apply
 *
 * Behavior:
 *  - Reads field_dpi as a DPI value (e.g. 72, 300). If missing or invalid uses default DPI (72).
 *  - For each mapping source_field -> target_field: computes floor(source_value * dpi)
 *    and writes the integer into target_field only if the computed value differs from current.
 *  - By default it's a dry-run; add --apply to persist changes.
 */

use Drupal\node\Entity\Node;

if (PHP_SAPI !== 'cli') {
  echo "This script must be run from the command line (drush scr recommended).\n";
  exit(1);
}

// Parse CLI args. Drush forwards arguments after a "--" so look in $argv.
$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
$apply = in_array('--apply', $argv, true);

// Default DPI to use when node->field_dpi is missing or invalid.
$default_dpi = 72.0;

// Edit this map if your source fields are named differently.
// Keys = source field name (e.g. coordinate in inches)
// Values = destination pixel field name (will be set to floor(source * dpi))
$fields_map = [
  'field_top_x'    => 'field_top_x_pixels',
  'field_top_y'    => 'field_top_y_pixels',
  'field_bottom_x' => 'field_bottom_x_pixels',
  'field_bottom_y' => 'field_bottom_y_pixels',
];

// If your source fields already have the *_pixels suffix and you want to overwrite them,
// set the mapping to map the field to itself, e.g. 'field_top_x_pixels' => 'field_top_x_pixels'

$batch_size = 50;
$updated_count = 0;
$processed_count = 0;
$skipped_count = 0;

echo "Starting composite nodes DPI -> pixels update (" . ($apply ? "APPLYING CHANGES" : "DRY RUN") . ")\n";

// Load entity query for nodes of type composite
$query = \Drupal::entityQuery('node')
  ->condition('type', 'composite')
  ->accessCheck(FALSE);

// Execute to get nids
$nids_all = $query->execute();
$total = count($nids_all);
if ($total === 0) {
  echo "No nodes of type 'composite' found.\n";
  exit(0);
}

echo "Found {$total} composite nodes. Processing in batches of {$batch_size}.\n";

$nids_chunks = array_chunk($nids_all, $batch_size);
foreach ($nids_chunks as $chunk_index => $nids) {
  /** @var \Drupal\node\NodeInterface[] $nodes */
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);

  foreach ($nodes as $node) {
    $processed_count++;

    // Read DPI and validate; if missing/invalid use default.
    $dpi_raw = $node->get('field_dpi')->value;
    $dpi = is_numeric($dpi_raw) ? (float) $dpi_raw : null;
    if ($dpi === null || $dpi <= 0) {
      $dpi = $default_dpi;
      $dpi_note = "(used default {$default_dpi})";
    } else {
      $dpi_note = "";
    }

    $node_changes = [];
    $made_change = false;

    foreach ($fields_map as $src_field => $target_field) {
      // If source field missing, skip.
      if (!$node->hasField($src_field)) {
        $node_changes[] = "SKIP missing source field {$src_field}";
        continue;
      }

      // Read source value (assumes single-value numeric field)
      $src_value_raw = $node->get($src_field)->value;
      if ($src_value_raw === null || $src_value_raw === '') {
        $node_changes[] = "SKIP empty {$src_field}";
        continue;
      }

      if (!is_numeric($src_value_raw)) {
        $node_changes[] = "SKIP non-numeric {$src_field}='{$src_value_raw}'";
        continue;
      }

      $src_value = (float) $src_value_raw;
      $pixels = (int) floor($src_value * $dpi);

      // If target field does not exist on the node, report and skip
      if (!$node->hasField($target_field)) {
        $node_changes[] = "SKIP missing target field {$target_field}";
        continue;
      }

      // Read existing target value (if any)
      $existing_raw = $node->get($target_field)->value;
      $existing = ($existing_raw === null || $existing_raw === '') ? null : (is_numeric($existing_raw) ? (int) $existing_raw : $existing_raw);

      if ($existing === $pixels) {
        $node_changes[] = "{$target_field}: unchanged ({$pixels})";
        continue;
      }

      // Set the new integer pixel value
      $node->set($target_field, $pixels);
      $node_changes[] = "{$target_field}: {$existing} -> {$pixels}";
      $made_change = true;
    }

    if ($made_change && $apply) {
      try {
        $node->save();
        $updated_count++;
        echo "[{$processed_count}/{$total}] Saved node {$node->id()} (dpi={$dpi} {$dpi_note}): " . implode('; ', $node_changes) . "\n";
      }
      catch (\Exception $e) {
        echo "[{$processed_count}/{$total}] ERROR saving node {$node->id()}: " . $e->getMessage() . "\n";
      }
    } else {
      // Dry-run or no changes
      if (!empty($node_changes)) {
        echo "[{$processed_count}/{$total}] Would update node {$node->id()} (dpi={$dpi} {$dpi_note}): " . implode('; ', $node_changes) . "\n";
      } else {
        $skipped_count++;
        echo "[{$processed_count}/{$total}] No updates for node {$node->id()} (dpi={$dpi} {$dpi_note}).\n";
      }
    }
  }

  // Free memory
  unset($nodes);
}

echo "Processing complete. Processed: {$processed_count}. ";
if ($apply) {
  echo "Nodes updated: {$updated_count}. Skipped (no change or missing fields): {$skipped_count}.\n";
} else {
  echo "Dry-run: no nodes were saved. To apply changes re-run with --apply.\n";
}