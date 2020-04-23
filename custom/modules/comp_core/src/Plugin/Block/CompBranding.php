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
      <div class="block-system-branding-block">
        <h1 class="mt-3 mb-3">UNB Class Composite & Group Photographs</h1>
      </div>
    ';

    return [
      '#markup' => $this->t($text),
    ];
  }

}
