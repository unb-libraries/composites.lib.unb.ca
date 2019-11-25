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
     *   The prepare-row event.
     */
    public function onPrepareRow(MigratePrepareRowEvent $event) {
      $row = $event->getRow();
      $migration = $event->getMigration();
      $migration_id = $migration->id();

      $row->setSourceProperty('top_x', 100);
    }
}
