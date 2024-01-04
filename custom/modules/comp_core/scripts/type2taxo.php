<?php

/**
 * @file
 * Contains type2taxo.php.
 *
 * Copies Type field text to taxonomy for composites in for composites.lib.unb.ca.
 */

 type2taxo();

/**
 * Copy Type field text to taxonomy.
 */
function type2taxo() {

  $ids = \Drupal::entityQuery('node')
    ->condition('type', 'composite')
    ->accessCheck(FALSE)
    ->execute();

  if (!empty($ids)) {
    foreach ($ids as $id) {
      $composite = Node::load($id) ?? NULL;

      if ($composite) {
        $title = $composite->getName();
        $composite->save();
        echo "[-] [$title]->[Updated]\n";
      }
    }
  }
}

/**
 * Add multiple terms to a given vocabulary.
 *
 * @param string $vid
 *   A string indicating the id of the vocabulary to update.
 * @param array $terms
 *   An array containing the names of the terms to add.
 * @param int $parent_id
 *   The ID of the parent term, if any.
 */
function add_terms(string $vid, array $terms, int $parent_id = NULL) {

  foreach ($terms as $term) {
    $new_term = Term::create([
      'vid' => $vid,
      'name' => $term,
    ]);

    $new_term->set('parent', $parent_id);
    $new_term->save();

    echo "[+] [$term]->[$vid]\n";
    $new_terms[] = $new_term->id();
  }

  return $new_terms;
