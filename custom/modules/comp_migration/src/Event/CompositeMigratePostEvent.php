<?php

namespace Drupal\comp_migration\Event;

use Drupal\comp_migration\CompMigrationTrait;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
    if ($migration_id == 'comp_1_composites') {
      $destination_ids = $event->getDestinationIdValues();
      $cid = $destination_ids[0];
      // Load and save migrated node (to apply "onsave" operations).
      $composite = Node::load($cid);
      $composite->save();
    }
  }

}
