<?php

/**
 * @file
 * Contains type2taxo.php.
 *
 * Copies Type field text to taxonomy for composites in for composites.lib.unb.ca.
 */
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

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
        $title = $composite->title->getValue()[0]['value'];
        $type = $composite->field_type->getValue()[0]['value'];
        $type = $type == "Encoenia" ? "Encaenia" : $type;
        $type = $type == "Canadian Officers Training Corps" ? 
          "Canadian Officers' Training Corps" : $type;
        $composite->field_composite_type->setvalue(find_add_term('composite_types', $type));
        echo dump($composite->field_composite_type->getvalue());
        $composite->save();
        echo "[-] [$title]->[Processed]\n";
      }
    }
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
function find_add_term(string $vid, string $name) {
  $name = trim($name);

  $terms = Drupal::entityQuery('taxonomy_term')
    ->condition('vid', $vid)
    ->condition('name', $name, 'LIKE')
    ->accesscheck(false)
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