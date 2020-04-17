<?php

namespace Drupal\comp_migration;

/**
 * Trait for defining global properties for COMP migrations.
 */
trait CompMigrationTrait {

  /**
   * Get the current migrations.
   *
   * @return array
   *   An array containing applicable migration IDs.
   */
  public function getMigrations() {
    return [
      'comp_1_subjects',
      'comp_2_composites',
    ];
  }

}
