<?php

namespace Drupal\comp_migration\Event;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\comp_migration\CompMigrationTrait;
use Drupal\migrate_plus\Event\MigrateEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the migrate event subscriber.
 */
class SubjectMigrateEvent implements EventSubscriberInterface {

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

    // Only act on rows for this migration.
    if ($migration_id == 'comp_1_subjects') {

      // Subject photo coordinates.
      if (strtolower($row->getSourceProperty('coord_top')) == 'absent') {
        $row->setSourceProperty('absent', TRUE);
        $row->setSourceProperty('top_x', 0);
        $row->setSourceProperty('top_y', 0);
        $row->setSourceProperty('bottom_x', 0);
        $row->setSourceProperty('bottom_y', 0);
      }
      else {
        // Top.
        $coord_top = explode(',', $row->getSourceProperty('coord_top'));
        $top_x = !empty(floatval($coord_top[0])) ? floatval($coord_top[0]) : 0;
        $top_y = !empty(floatval($coord_top[1])) ? floatval($coord_top[1]) : 0;

        $row->setSourceProperty('top_x', $top_x);
        $row->setSourceProperty('top_y', $top_y);
        // Bottom.
        $coord_bottom = explode(',', $row->getSourceProperty('coord_bottom'));
        $bottom_x = !empty(floatval($coord_bottom[0])) ? floatval($coord_bottom[0]) : 0;
        $bottom_y = !empty(floatval($coord_bottom[1])) ? floatval($coord_bottom[1]) : 0;

        $row->setSourceProperty('bottom_x', $bottom_x);
        $row->setSourceProperty('bottom_y', $bottom_y);
      }

      // Gender.
      $src_gender = strtoupper($row->getSourceProperty('gender'));

      switch ($src_gender) {
        default:
          $gender = "Not Identified";
          break;

        case "F":
          $gender = "Female";
          break;

        case "M":
          $gender = "Male";
          break;
      }

      $terms = $this->typeManager->getStorage('taxonomy_term')->getQuery()
        ->condition('vid', 'gender')
        ->condition('name', $gender)
        ->execute();

      reset($terms);
      $tid = key($terms);
      $row->setSourceProperty('taxo_gender', $tid);

      // Campus.
      $terms = $this->typeManager->getStorage('taxonomy_term')->getQuery()
        ->condition('vid', 'campus')
        ->condition('name', 'Fredericton')
        ->execute();

      reset($terms);
      $tid = key($terms);
      $row->setSourceProperty('field_subject_campus', $tid);

      // Awards.
      $src_awards = $row->getSourceProperty('awards');
      $awards_multi = explode(';', $src_awards);
      $row->setSourceProperty('awards_multi', $awards_multi);
    }
  }

}
