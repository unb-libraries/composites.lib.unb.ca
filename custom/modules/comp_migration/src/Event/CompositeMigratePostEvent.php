<?php

namespace Drupal\comp_migration\Event;

use Drupal\comp_migration\CompMigrationTrait;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Defines the migrate event subscriber.
 */
class CompositeMigratePostEvent implements EventSubscriberInterface {

  use CompMigrationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::POST_ROW_SAVE => [['onPostRowSave', 0]]];
  }

  /**
   * After new row.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post-row event.
   *   Some other transformations from the original data to the
   *   new content types will take place here. Post row migration.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration();
    $migration_id = $migration->id();
    $row = $event->getRow();

    // Only act on rows for this migration.
    if ($migration_id == 'comp_2_composites') {
      $destination_ids = $event->getDestinationIdValues();
      $cid = $destination_ids[0];
      // Generate title and save node (to apply entity update operations).
      $composite = Node::load($cid);
      $year = $composite->get('field_comp_year')->getValue()[0]['value'];
      $type = $composite->get('field_type')->getValue()[0]['value'];
      $title = "$year $type Photo";

      $composite->set('title', $title);
      $composite->save();

      // Generate high resolution DZI tiles.
      $fid = $composite->get('field_image')->getValue()[0]['target_id'];
      $nid = $composite->id();
      $this->generateDziTiles($fid, $nid);
    }
  }

  /**
   * Generates DZI tiles for the given image file.
   */
  public function generateDziTiles($fid, $nid) {
    $file = File::load($fid);
    $filename = $file->getFilename();
    // Cut extension from filename.
    $fn_plain = substr($filename, 0, -4);
    // Get image path.
    $img_location = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . "/comp_images";
    $img_path = $img_location . "/$filename";
    $dzi_path = $img_location . "/dzi/composite_$nid";

    $command = "/usr/local/bin/magick-slicer -e jpg -i \"{$img_path}\"" .
     " -o \"{$dzi_path}\"";

    // Run command in shell.
    exec($command, $output, $return);

    // For batch process.
    $context['message'] = t(
      'Generated DZI tiles for high-resolution composite file @file',
      [
        '@file' => $fn_plain,
      ]
    );
  }

}
