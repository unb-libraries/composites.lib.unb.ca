<?php

namespace Drupal\comp_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a custom block for site branding.
 *
 * @Block(
 *   id = "comp_branding",
 *   admin_label = @Translation("Custom Branding"),
 *   category = @Translation("Misc"),
 * )
 */
class CompBranding extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $text = '
      <a href="/"><h1>UNB Class Composite & Group Photographs</h1></a>
    ';

    return [
      '#markup' => $this->t($text),
    ];
  }

}
