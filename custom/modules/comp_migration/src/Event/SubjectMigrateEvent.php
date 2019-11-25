<?php

namespace Drupal\comp_migration\Event;

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
   * The prepare-row event.
   * This is where most of the transformations from the original data to the
   * new content types will take place. Post row migration.
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    // Get row and migration.
    $row = $event->getRow();
    $migration = $event->getMigration();
    $migration_id = $migration->id();

    // Subject photo coordinates.
    // Top.
    $coord_top = explode(',', $row->getSourceProperty('coord_top'));
    $top_x = (float) $coord_top[0];
    $top_y = (float) $coord_top[1];

    $row->setSourceProperty('top_x', $top_x);
    $row->setSourceProperty('top_y', $top_y);
    // Bottom.
    $coord_bottom = explode(',', $row->getSourceProperty('coord_bottom'));
    $bottom_x = (float) $coord_bottom[0];
    $bottom_y = (float) $coord_bottom[1];

    $row->setSourceProperty('bottom_x', $bottom_x);
    $row->setSourceProperty('bottom_y', $bottom_y);
  }
}
