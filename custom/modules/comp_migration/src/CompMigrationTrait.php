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
   *   An associative array containing applicable migration IDs.
   */

   public function getMigrations() {
     return [
       'comp_composites',
       'comp_subjects',
     ];
   }
}
