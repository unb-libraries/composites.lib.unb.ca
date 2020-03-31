<?php

namespace Drupal\comp_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a custom Browse block for the home page.
 *
 * @Block(
 *   id = "comp_browse",
 *   admin_label = @Translation("Browse"),
 *   category = @Translation("Misc"),
 * )
 */
class CompBrowse extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $text = '
      <p>
        <a href="/browse-composites">By Year</a>
        <br>
        <a href="/browse-subjects">All People</a>
      </p>
    ';

    return [
      '#markup' => $this->t($text),
    ];
  }

}
