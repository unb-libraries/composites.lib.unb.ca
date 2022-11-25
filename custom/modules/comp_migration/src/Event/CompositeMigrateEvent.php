<?php

namespace Drupal\comp_migration\Event;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileSystemInterface;
use Drupal\comp_migration\CompMigrationTrait;
use Drupal\migrate_plus\Event\MigrateEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the migrate event subscriber.
 */
class CompositeMigrateEvent implements EventSubscriberInterface {

  use CompMigrationTrait;

  /**
   * Entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $typeManager;

  /**
   * Constructs a new EventSubscriberInterface object.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $type_manager
   *   The entity type manager service class.
   */
  public function __construct(EntityTypeManager $type_manager) {
    $this->typeManager = $type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PREPARE_ROW][] = ['onPrepareRow', 0];
    return $events;
  }

  /**
   * React to a new row.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare-row event.
   *   This is where most of the transformations from the original data to the
   *   new content types will take place. Post row migration.
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    // Transformations for fields not copied straight from source.
    // Get row and migration.
    $row = $event->getRow();
    $migration = $event->getMigration();
    $migration_id = $migration->id();

    // LEGACY COMPOSITES MIGRATION.
    if ($migration_id == 'comp_2_composites') {

      // Contributors.
      $src_contribs = $row->getSourceProperty('contributor');
      $contribs = explode(',', $src_contribs);
      // Term IDs.
      $tids = [];

      foreach ($contribs as $contrib) {
        $tid = $this->findAddTerm('contributor', $contrib);
        $tids[] = $tid ? $tid : NULL;
      }

      $row->setSourceProperty('taxo_contributors', $tids);

      // Buildings (current name).
      $src_buildings = $row->getSourceProperty('buildings_current');
      $buildings = explode(',', $src_buildings);
      // Term IDs.
      $tids = [];

      foreach ($buildings as $building) {
        $tid = $this->findAddTerm('building', $building);
        $tids[] = $tid ? $tid : NULL;
      }

      $row->setSourceProperty('taxo_buildings_current', $tids);

      // Buildings (previous name).
      $src_buildings = $row->getSourceProperty('buildings_former');
      $buildings = explode(',', $src_buildings);
      // Term IDs.
      $tids = [];

      foreach ($buildings as $building) {
        $tid = $this->findAddTerm('building', $building);
        $tids[] = $tid ? $tid : NULL;
      }

      $row->setSourceProperty('taxo_buildings_former', $tids);

      // Photographer.
      $src_photographer = $row->getSourceProperty('photographer');

      if (!empty($src_photographer)) {
        $tid = $this->findAddTerm('photographer', $src_photographer);
        $row->setSourceProperty('taxo_photographer', $tid);
      }

      // Campus.
      // On migration, default to Fredericton.
      $src_campus = 'Fredericton';
      $tid = $this->findAddTerm('campus', $src_campus);
      $row->setSourceProperty('taxo_campus', $tid);

      // Subjects.
      // Composite source file.
      $src_file_comp = trim($row->getSourceProperty('source_file'));

      // Search for subjects with same source file.
      if (!empty($src_file_comp)) {
        $nids = $this->typeManager->getStorage('node')->getQuery()
          ->condition('type', 'subject')
          ->condition('field_source_file', $src_file_comp)
          ->execute();
      }

      $subjects = [];

      foreach ($nids as $nid) {
        $subjects[] = ['target_id' => $nid];
      }

      if (!empty($subjects)) {
        $row->setSourceProperty('entity_subjects', $subjects);
      }

      // Image.
      $src_path = drupal_get_path('module', 'comp_migration') . '/data/img/';
      $src_filename = trim($row->getSourceProperty('source_file'));
      $src_ext = '.tif';

      // If no TIFF is found, try JPEG.
      if (!file_exists($src_path . $src_filename . $src_ext)) {
        $src_ext = '.jpg';
      }

      $full_path .= $src_path . $src_filename . $src_ext;
      $data = file_exists($full_path) ? file_get_contents($full_path) : NULL;

      if (!empty($data)) {
        $file = file_save_data($data, "public://comp_images/" . $src_filename . $src_ext, FileSystemInterface::EXISTS_REPLACE);
        $fid = $file->id();

        $field_image = [
          'target_id' => $fid,
          'alt' => $src_filename,
          'title' => $src_filename,
        ];

        $row->setSourceProperty('drupal_image', $field_image);
      }

      // Related Image.
      $src_filename = trim($row->getSourceProperty('related_image'));

      // Checking for absence of related img prevents file_get_contents warning.
      if (!empty($src_filename)) {
        $src_filename .= ".jpg";
        $src_path = drupal_get_path('module', 'comp_migration') . '/data/img/'
          . $src_filename;

        $data = file_exists($src_path) ? file_get_contents($src_path) : NULL;

        if (!empty($data)) {
          $file = file_save_data($data, "public://comp_images/" . $src_filename, FileSystemInterface::EXISTS_REPLACE);
          $fid = $file->id();

          $field_rel_image = [
            'target_id' => $fid,
            'alt' => $src_filename,
            'title' => $src_filename,
          ];

          $row->setSourceProperty('drupal_rel_image', $field_rel_image);
        }
      }

      // Year.
      $src_date = $row->getSourceProperty('date');
      $src_period = empty($src_date) ? NULL : substr($src_date, 0, 4);
      $row->setSourceProperty('comp_year', $year);
    }

    // SPORTS PHOTOS MIGRATION.
    if ($migration_id == 'comp_3_sports') {
      // Title.
      $src_title = $row->getSourceProperty('src_title');
      $src_period = $row->getSourceProperty('src_period');
      // Split on dash.
      $parts = explode('-', $src_title);

      // Title case and trim space.
      foreach ($parts as $key => $part) {
        $parts[$key] = trim(ucwords(strtolower($part)));
      }

      $sport = !empty($parts[0]) ? trim(ucwords(strtolower($parts[0]))) . ' ' :
        NULL;
      $division = !empty($parts[1]) ? '(' .
        trim(ucfirst(strtolower($parts[1]))) . ') ' : NULL;

      // Extract 4-digit numbers (years) from period.
      preg_match('/\d{4}/', $src_period, $matches);

      if (!empty($matches)) {
        $year = min($matches) . ' ';
      }
      else {
        $year = "n.d. ";
      }

      $row->setSourceProperty('post_title', "$year$sport$division" . 'Sports Photo');

      // Contributors and photographers.
      $contribs = [
        [
          'name' => $row->getSourceProperty('contrib1_name'),
          'role' => strtolower(trim($row->getSourceProperty('contrib1_role'))),
        ],
        [
          'name' => $row->getSourceProperty('contrib2_name'),
          'role' => strtolower(trim($row->getSourceProperty('contrib2_role'))),
        ],
        [
          'name' => $row->getSourceProperty('contrib3_name'),
          'role' => strtolower(trim($row->getSourceProperty('contrib3_role'))),
        ],
      ];

      // Contributor IDs.
      $cids = [];

      foreach ($contribs as $contrib) {
        echo print_r("\n$year$sport$division\n");
        echo print_r("\n" . $contrib['role'] . "\n");
        if ($contrib['role'] == 'photographer') {
          $pid = $this->findAddTerm('photographer', $contrib['name']);
          echo print_r($pid);
        }
        elseif ($contrib['role'] != 'scanner' and $contrib['role'] != 'illustrator') {
          $cid = $this->findAddTerm('contributor', $contrib['name']);
          echo print_r("\n$cid\n");
          $cids[] = $cid ? $cid : NULL;
        }
      }

      $row->setSourceProperty('taxo_contributors', $cids);
      $row->setSourceProperty('taxo_photographer', $pid);
    }
  }

  /**
   * Find or create taxonomy term.
   *
   * @param string $vid
   *   The ID of the vocabulary for the term.
   * @param string $name
   *   The name for the term.
   *
   * @return int
   *   The ID of the term (int).
   */
  public function findAddTerm(string $vid, string $name) {
    $name = trim($name);

    $terms = $this->typeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', $vid)
      ->condition('name', $name)
      ->execute();

    reset($terms);
    $tid = key($terms);

    if (empty($tid)) {
      if ($name != NULL) {
        $term = Term::create([
          'name' => $name,
          'vid' => $vid,
        ]);

        $term->save();
        $tid = $term->id();
      }
      else {
        $tid = NULL;
      }
    }

    return $tid;
  }

}
