<?php

namespace Drupal\comp_migration\Event;

use Drupal\comp_migration\CompMigrationTrait;
use Drupal\migrate_plus\Event\MigrateEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Defines the migrate event subscriber.
 */
class CompositeMigrateEvent implements EventSubscriberInterface {

  use CompMigrationTrait;

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

    // Subjects.
    // Composite source file.
    $src_file_comp = trim($row->getSourceProperty('source_file'));

    // Search for subjects with same source file.
    if (!empty($src_file_comp)) {
      $nids = \Drupal::entityQuery('node')
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
    $src_filename = trim($row->getSourceProperty('source_file')) . ".jpg";
    $src_path = drupal_get_path('module', 'comp_migration') . '/data/img/'
      . $src_filename;
    $data = file_get_contents($src_path);

    if (!empty($data)) {
      $file = file_save_data($data, "public://" . $src_filename, FILE_EXISTS_REPLACE);
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

    // Checking for absence of related image prevents file_get_contents warning.
    if (!empty($src_filename)) {
      $src_filename .= ".jpg";
      $src_path = drupal_get_path('module', 'comp_migration') . '/data/img/'
        . $src_filename;
      $data = file_get_contents($src_path);

      if (!empty($data)) {
        $file = file_save_data($data, "public://" . $src_filename, FILE_EXISTS_REPLACE);
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
    $year = empty($src_date) ? NULL : substr($src_date, 0, 4);
    $row->setSourceProperty('comp_year', $year);
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

    $terms = \Drupal::entityQuery('taxonomy_term')
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
