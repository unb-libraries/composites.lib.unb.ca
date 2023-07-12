<?php

namespace Drupal\comp_migration\Event;

use Drupal\comp_migration\CompMigrationTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the migrate event subscriber.
 */
class CompositeMigratePostEvent implements EventSubscriberInterface {

  use CompMigrationTrait;
  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $typeManager;

  /**
   * Configuration.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * File system.
   *
   * @var Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs a new EventSubscriberInterface object.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $type_manager
   *   The entity type manager service object.
   * @param Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory service object.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service object.
   */
  public function __construct(
    EntityTypeManager $type_manager,
    ConfigFactory $config_factory,
    FileSystem $file_system) {
    $this->typeManager = $type_manager;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
  }

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

    // Only act on rows for legacy composites migration.
    if ($migration_id == 'comp_2_composites') {
      $destination_ids = $event->getDestinationIdValues();
      $cid = $destination_ids[0];
      // Generate title and save node (to apply entity update operations).
      $composite = $this->typeManager->getStorage('node')->load($cid);
      $year = $composite->get('field_comp_year')->getValue()[0]['value'];
      $type = $composite->get('field_type')->getValue()[0]['value'];
      $title = "$year $type Photo";

      $composite->set('title', $title);
      $composite->save();
    }

    // Only act on rows for legacy spots photos migration.
    if ($migration_id == 'comp_3_sports') {
      $destination_ids = $event->getDestinationIdValues();
      $cid = $destination_ids[0];
      // Sort subjects by last name.
      // Retrieve subject node ids associated to composite.
      $composite = $this->typeManager->getStorage('node')->load($cid);
      $sids = $composite->get('field_subjects')->getValue();

      // Retrieve title (name) for each id.
      foreach ($sids as $k => $sid) {
        $subject =
          $this->typeManager->getStorage('node')->load($sid['target_id']);
        $sid['name'] = $subject->getTitle();
        $sids[$k] = $sid;
        // Since we have the subject, add a reference back to the composite.
        $comp_ref = ['target_id' => $cid];
        $subject->set('field_composite', $comp_ref);
        $subject->save();
      }

      // Sort multidimensional array by name column.
      array_multisort(array_column($sids, 'name'), SORT_ASC, $sids);

      // Create array with only the new sorted 'target_id'.
      $sorted_sids = [];

      foreach ($sids as $k => $sid) {
        $sorted_sids[$k] = ['target_id' => $sid['target_id']];
      }

      // Update field value with the sorted ids.
      $composite->get('field_subjects')->setValue($sorted_sids);
    }

    if ($migration_id == 'comp_2_composites' or
      $migration_id == 'comp_3_sports') {
      // Generate high resolution DZI tiles.
      $fid = $composite->get('field_image')->getValue()[0]['target_id'];

      if ($fid) {
        $nid = $composite->id();
        $this->generateDziTiles($fid, $nid);
      }
    }
  }

  /**
   * Generates DZI tiles for the given image file.
   */
  public function generateDziTiles($fid, $nid) {
    $file = $this->typeManager->getStorage('file')->load($fid);
    $filename = $file->getFilename();
    // Cut extension from filename.
    $fn_plain = substr($filename, 0, -4);
    // Get image path.
    $scheme = $this->configFactory->get('system.file')->get('default_scheme');
    $img_location = $this->fileSystem->realpath($scheme . "://") . "/comp_images";
    $img_path = $img_location . "/$filename";
    $dzi_path = $img_location . "/dzi/composite_$nid";

    $command = "/usr/local/bin/magick-slicer -e jpg -i \"{$img_path}\"" .
     " -o \"{$dzi_path}\"";

    // Run command in shell.
    exec($command, $output, $return);

    // For batch process.
    $context['message'] = $this->t(
      'Generated DZI tiles for high-resolution composite file @file',
      [
        '@file' => $fn_plain,
      ]
    );
  }

}
