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
     * The prepare-row event.
     * This is where most of the transformations from the original data to the
     * new content types will take place. Post row migration.
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
        $terms = \Drupal::entityQuery('taxonomy_term')
          ->condition('vid', 'contributor')
          ->condition('name', $contrib)
          ->execute();

        reset($terms);
        $tid = key($terms);

        if (empty($tid)) {
          $term = Term::create([
            'name' => $contrib,
            'vid' => 'contributor',
          ]);

          $term->save();
          $tid = $term->id();
        }

        $tids[] = $tid ? $tid : NULL;
      }

      $row->setSourceProperty('taxo_contributors', $tids);
    }
}
